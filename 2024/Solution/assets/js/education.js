/* Simple Education interactive scripts: quiz + matching game
 * - Quiz: simple multiple-choice quiz with localStorage high score
 * - Matching: drag-and-drop species-to-habitat matching
 */
document.addEventListener('DOMContentLoaded', function(){
  /* QUIZ */
  const quizEl = document.getElementById('edu-quiz');
  if (quizEl) {
    const questions = [
      {q: 'What do lions primarily eat?', a: ['Herbivores', 'Carnivores', 'Omnivores'], correct: 1},
      {q: 'Which habitat do flamingos prefer?', a: ['Desert', 'Wetlands', 'Forest'], correct: 1},
      {q: 'Which of these is a reptile?', a: ['Giraffe', 'Crocodile', 'Penguin'], correct: 1}
    ];
    let idx = 0, score = 0;
    const qText = quizEl.querySelector('.quiz-question');
    const answers = quizEl.querySelector('.quiz-answers');
    const nextBtn = quizEl.querySelector('.quiz-next');
    const render = () => {
      const cur = questions[idx];
      qText.textContent = (idx+1) + '. ' + cur.q;
      answers.innerHTML = '';
      cur.a.forEach((opt, i) => {
        const but = document.createElement('button');
        but.className = 'quiz-opt';
        but.textContent = opt;
        but.addEventListener('click', function(){
          if (i === cur.correct) { score++; addFlash('success', 'Correct!'); }
          else addFlash('error', 'Incorrect');
          // disable options
          answers.querySelectorAll('button').forEach(b => b.disabled = true);
        });
        answers.appendChild(but);
      });
    };
    nextBtn.addEventListener('click', function(){
      idx++;
      if (idx >= questions.length) {
        // show result
        quizEl.querySelector('.quiz-result').textContent = `You scored ${score}/${questions.length}`;
        // store high score
        try { localStorage.setItem('eduQuizHighScore', Math.max(score, localStorage.getItem('eduQuizHighScore')||0)); } catch(e){}
        quizEl.querySelector('.quiz-end').style.display = 'block';
        quizEl.querySelector('.quiz-play').style.display = 'none';
      } else render();
    });
    // initial render
    render();
  }

  /* MATCHING */
  const matchEl = document.getElementById('edu-match');
  if (matchEl) {
    const items = Array.from(matchEl.querySelectorAll('.match-item'));
    const zones = Array.from(matchEl.querySelectorAll('.match-zone'));
    items.forEach(i => {
      i.draggable = true;
      i.addEventListener('dragstart', e => { e.dataTransfer.setData('text/plain', i.dataset.key); });
    });
    zones.forEach(z => {
      z.addEventListener('dragover', e => { e.preventDefault(); });
      z.addEventListener('drop', e => {
        e.preventDefault();
        const key = e.dataTransfer.getData('text/plain');
        const item = matchEl.querySelector(`[data-key="${key}"]`);
        if (!item) return;
        // append item, check correctness
        z.appendChild(item);
      });
    });
    matchEl.querySelector('.match-check').addEventListener('click', function(){
      let correct = 0; let total = items.length;
      zones.forEach(z => {
        const zoneKey = z.dataset.key;
        const item = z.querySelector('.match-item');
        if (item && item.dataset.key === zoneKey) correct++;
      });
      addFlash('notice', `Match result: ${correct}/${total} correct`);
    });
  }

  function addFlash(type, message) {
    // very small flash notifier using existing push-flash helper if exists
    if (typeof window !== 'undefined') {
      alert(message);
    }
  }
});
