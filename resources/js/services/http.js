import axios from 'axios';
export const http = axios.create({
    timeout: 15000,
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json',
    },
});
http.interceptors.response.use(
    (response) => response,
    (error) => {
        if (axios.isCancel(error)) return Promise.reject(error);
        const messages = {
            401: 'Sua sessão terminou. Entre novamente.',
            403: 'Você não tem acesso a este serviço.',
            404: 'Não encontramos este registro.',
            419: 'Sua sessão expirou. Atualize a página.',
            409: 'A solicitação anterior ainda está sendo processada.',
            422: 'Confira os campos informados.',
            429: 'Aguarde um momento antes de tentar novamente.',
            500: 'Não foi possível concluir agora.',
            503: 'O serviço está indisponível. Tente novamente.',
        };
        error.userMessage =
            messages[error.response?.status] ||
            (error.code === 'ECONNABORTED'
                ? 'O serviço demorou a responder. Tente novamente.'
                : 'Não foi possível conectar. Confira sua conexão.');
        return Promise.reject(error);
    },
);
