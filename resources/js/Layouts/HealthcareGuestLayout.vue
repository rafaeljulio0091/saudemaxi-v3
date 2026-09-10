<script setup>
import '@/../css/healthcare/tokens.css';
import '@/../css/healthcare/components.css';
import '@/../css/healthcare/utilities.css';
</script>
<template>
    <div class="sm-app sm-auth">
        <aside class="sm-auth-art">
            <p class="sm-kicker">Saúde Maxi</p>
            <h1>
                Seu cuidado começa
                <br />
                com uma conversa.
            </h1>
            <p>Orientação, atendimento e seus cuidados em um só lugar.</p>
            <div class="sm-row">
                <span class="sm-badge">Mais perto de você</span>
                <span class="sm-badge">Simples de usar</span>
            </div>
        </aside>
        <main class="sm-auth-form"><slot /></main>
    </div>
</template>
