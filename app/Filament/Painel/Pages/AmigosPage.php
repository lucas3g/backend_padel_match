<?php

namespace App\Filament\Painel\Pages;

use App\Models\Friend;
use App\Models\Player;
use App\Models\PlayerFavorite;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AmigosPage extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Amigos';
    protected static ?int    $navigationSort  = 2;

    protected static string $view = 'filament.painel.pages.amigos-page';

    public string $activeTab   = 'amigos';
    public string $searchQuery = '';

    private function currentPlayer(): ?Player
    {
        return auth()->user()?->player;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab   = $tab;
        $this->searchQuery = '';
    }

    public function aceitarAmigo(int $friendshipId): void
    {
        $record = Friend::where('id', $friendshipId)
            ->where('friend_id', $this->currentPlayer()?->id)
            ->where('status', 'pending')
            ->first();

        if (! $record) {
            Notification::make()->title('Pedido não encontrado.')->danger()->send();
            return;
        }

        $record->update(['status' => 'accepted']);
        Notification::make()->title('Amizade aceita!')->success()->send();
    }

    public function rejeitarAmigo(int $friendshipId): void
    {
        $record = Friend::where('id', $friendshipId)
            ->where('friend_id', $this->currentPlayer()?->id)
            ->where('status', 'pending')
            ->first();

        if (! $record) {
            Notification::make()->title('Pedido não encontrado.')->danger()->send();
            return;
        }

        $record->update(['status' => 'rejected']);
        Notification::make()->title('Pedido rejeitado.')->warning()->send();
    }

    public function cancelarPedido(int $friendshipId): void
    {
        $record = Friend::where('id', $friendshipId)
            ->where('player_id', $this->currentPlayer()?->id)
            ->where('status', 'pending')
            ->first();

        if (! $record) {
            Notification::make()->title('Pedido não encontrado.')->danger()->send();
            return;
        }

        $record->delete();
        Notification::make()->title('Pedido cancelado.')->success()->send();
    }

    public function removerAmigo(int $friendPlayerId): void
    {
        $playerId = $this->currentPlayer()?->id;

        Friend::where(function ($q) use ($playerId, $friendPlayerId) {
            $q->where('player_id', $playerId)->where('friend_id', $friendPlayerId);
        })->orWhere(function ($q) use ($playerId, $friendPlayerId) {
            $q->where('player_id', $friendPlayerId)->where('friend_id', $playerId);
        })->where('status', 'accepted')->delete();

        PlayerFavorite::where('player_id', $playerId)
            ->where('favorite_player_id', $friendPlayerId)
            ->delete();

        Notification::make()->title('Amigo removido.')->success()->send();
    }

    public function toggleFavorito(int $friendPlayerId): void
    {
        $playerId = $this->currentPlayer()?->id;

        $exists = PlayerFavorite::where('player_id', $playerId)
            ->where('favorite_player_id', $friendPlayerId)
            ->exists();

        if ($exists) {
            PlayerFavorite::where('player_id', $playerId)
                ->where('favorite_player_id', $friendPlayerId)
                ->delete();
            Notification::make()->title('Removido dos favoritos.')->success()->send();
        } else {
            PlayerFavorite::create([
                'player_id'          => $playerId,
                'favorite_player_id' => $friendPlayerId,
            ]);
            Notification::make()->title('Adicionado aos favoritos!')->success()->send();
        }
    }

    public function enviarPedido(int $targetPlayerId): void
    {
        $playerId = $this->currentPlayer()?->id;

        $alreadyExists = Friend::where(function ($q) use ($playerId, $targetPlayerId) {
            $q->where('player_id', $playerId)->where('friend_id', $targetPlayerId);
        })->orWhere(function ($q) use ($playerId, $targetPlayerId) {
            $q->where('player_id', $targetPlayerId)->where('friend_id', $playerId);
        })->exists();

        if ($alreadyExists) {
            Notification::make()->title('Já existe uma relação com este jogador.')->warning()->send();
            return;
        }

        Friend::create([
            'player_id' => $playerId,
            'friend_id' => $targetPlayerId,
            'status'    => 'pending',
        ]);

        Notification::make()->title('Pedido de amizade enviado!')->success()->send();
    }

    protected function getViewData(): array
    {
        $player = $this->currentPlayer();

        if (! $player) {
            return [
                'amigos'       => collect(),
                'recebidos'    => collect(),
                'enviados'     => collect(),
                'buscarResult' => collect(),
                'favoriteIds'  => collect(),
            ];
        }

        $amigoIds = Friend::where('status', 'accepted')
            ->where(function ($q) use ($player) {
                $q->where('player_id', $player->id)->orWhere('friend_id', $player->id);
            })
            ->get()
            ->map(fn ($f) => $f->player_id === $player->id ? $f->friend_id : $f->player_id)
            ->unique();

        $amigos = Player::whereIn('id', $amigoIds)->get(['id', 'full_name', 'level', 'side']);

        $recebidos = Friend::where('friend_id', $player->id)
            ->where('status', 'pending')
            ->with('player:id,full_name,level')
            ->get();

        $enviados = Friend::where('player_id', $player->id)
            ->where('status', 'pending')
            ->with('friend:id,full_name,level')
            ->get();

        $favoriteIds = PlayerFavorite::where('player_id', $player->id)
            ->pluck('favorite_player_id');

        $buscarResult = collect();
        if ($this->activeTab === 'buscar' && mb_strlen(trim($this->searchQuery)) >= 2) {
            $excludeIds = Friend::where(function ($q) use ($player) {
                    $q->where('player_id', $player->id)->orWhere('friend_id', $player->id);
                })
                ->where('status', '!=', 'rejected')
                ->get()
                ->flatMap(fn ($f) => [$f->player_id, $f->friend_id])
                ->push($player->id)
                ->unique();

            $buscarResult = Player::where('full_name', 'like', '%' . trim($this->searchQuery) . '%')
                ->whereNotIn('id', $excludeIds)
                ->limit(20)
                ->get(['id', 'full_name', 'level', 'side']);
        }

        return compact('amigos', 'recebidos', 'enviados', 'buscarResult', 'favoriteIds');
    }
}
