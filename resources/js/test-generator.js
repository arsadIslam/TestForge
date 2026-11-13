/**
 * Alpine component for "Auto-select questions" on the test builder: rule rows of
 * subject · topic (any) · difficulty (any) · how many. Registered in app.js as
 * Alpine.data('testGenerator'). The server (TestService::generate) does the random picking.
 */
let nextKey = 0;

const blankRule = () => ({ key: nextKey++, subject_id: '', topic_id: '', difficulty: '', count: 5 });

export default ({ subjects, maxRules, oldRules = null }) => ({
    subjects, // [{ id, name, topics: [{ id, name }] }]
    maxRules,
    rules: oldRules?.length
        ? oldRules.map((rule) => ({ key: nextKey++, subject_id: String(rule.subject_id ?? ''), topic_id: String(rule.topic_id ?? ''), difficulty: rule.difficulty ?? '', count: Number(rule.count ?? 5) }))
        : [blankRule()],

    /** Topics of the rule's subject (the topic list follows the subject). */
    topicsFor(rule) {
        return this.subjects.find((subject) => String(subject.id) === String(rule.subject_id))?.topics ?? [];
    },

    subjectChanged(rule) {
        rule.topic_id = '';
    },

    get total() {
        return this.rules.reduce((sum, rule) => sum + (Number(rule.count) || 0), 0);
    },

    get canAdd() {
        return this.rules.length < this.maxRules;
    },

    add() {
        if (this.canAdd) this.rules.push(blankRule());
    },

    remove(index) {
        if (this.rules.length > 1) this.rules.splice(index, 1);
    },
});
