/**
 * Contains the data for a GitHub user.
 */
export class User {

  constructor(user) {
    this.user = user;
  }

  /**
   * Get the ID of the user.
   *
   * @returns {int}
   */
  getId() {
    return this.user.id;
  }

  /**
   * Get the login name of the user.
   *
   * @returns {string}
   */
  getLogin() {
    return this.user.login;
  }

  /**
   * Get the type of the user.
   *
   * @returns {"User"|"Bot"}
   */
  getType() {
    return this.user.type;
  }

  /**
   * Whether the user is a bot or not.
   *
   * @returns {boolean}
   */
  isBot() {
    return this.user.type === "Bot";
  }

  /**
   * Whether the user is a real person or not.
   *
   * @returns {boolean}
   */
  isUser() {
    return this.user.type === "User";
  }

}
