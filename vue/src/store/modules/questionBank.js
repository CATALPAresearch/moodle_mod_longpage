/**
 * Vuex module for question bank management
 */

export default {
  namespaced: true,

  state: () => ({
    questions: [],
    currentQuestionIndex: 0,
    loading: false,
    error: null,
  }),

  getters: {
    currentQuestion: (state) => {
      return state.questions[state.currentQuestionIndex] || null;
    },
    totalQuestions: (state) => state.questions.length,
    hasNextQuestion: (state) =>
      state.currentQuestionIndex < state.questions.length - 1,
    hasPreviousQuestion: (state) => state.currentQuestionIndex > 0,
  },

  mutations: {
    SET_QUESTIONS(state, questions) {
      state.questions = questions;
    },
    SET_CURRENT_QUESTION_INDEX(state, index) {
      state.currentQuestionIndex = Math.max(
        0,
        Math.min(index, state.questions.length - 1),
      );
    },
    SET_LOADING(state, loading) {
      state.loading = loading;
    },
    SET_ERROR(state, error) {
      state.error = error;
    },
    NEXT_QUESTION(state) {
      if (state.currentQuestionIndex < state.questions.length - 1) {
        state.currentQuestionIndex++;
      }
    },
    PREVIOUS_QUESTION(state) {
      if (state.currentQuestionIndex > 0) {
        state.currentQuestionIndex--;
      }
    },
    ADD_QUESTION(state, question) {
      state.questions.push(question);
    },
    UPDATE_QUESTION(state, { index, question }) {
      if (state.questions[index]) {
        state.questions.splice(index, 1, question);
      }
    },
    REMOVE_QUESTION(state, index) {
      state.questions.splice(index, 1);
      if (state.currentQuestionIndex >= state.questions.length) {
        state.currentQuestionIndex = Math.max(0, state.questions.length - 1);
      }
    },
  },

  actions: {
    async loadQuestionsForPage({ commit }, longpageid) {
      commit("SET_LOADING", true);
      commit("SET_ERROR", null);
      try {
        const response = await fetch(
          M.cfg.wwwroot + "/webservice/rest/server.php",
          {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
              wstoken: M.cfg.token,
              wsfunction: "mod_longpage_get_questions_for_page",
              longpageid,
              moodlewsrestformat: "json",
            }).toString(),
          },
        );

        if (!response.ok) throw new Error("Failed to fetch questions");
        const questions = await response.json();
        if (questions.exception) throw new Error(questions.message);
        commit("SET_QUESTIONS", questions);
      } catch (error) {
        commit("SET_ERROR", error.message);
      } finally {
        commit("SET_LOADING", false);
      }
    },

    setCurrentQuestion({ commit }, index) {
      commit("SET_CURRENT_QUESTION_INDEX", index);
    },

    nextQuestion({ commit }) {
      commit("NEXT_QUESTION");
    },

    previousQuestion({ commit }) {
      commit("PREVIOUS_QUESTION");
    },
  },
};
