(function () {
  "use strict";

  async function request(body) {
    const response = await fetch(splData.url, {
      method: "POST",
      credentials: "same-origin",
      body: body,
    });
    let payload = null;
    try {
      payload = await response.json();
    } catch (error) {
      throw new Error("The server returned an invalid response.");
    }
    if (!response.ok || !payload.success) {
      const message = payload && payload.data && payload.data.message
        ? payload.data.message
        : "The action could not be completed.";
      throw new Error(message);
    }
    return payload.data || {};
  }

  document.addEventListener("click", async function (event) {
    const actionButton = event.target.closest("[data-spl]");
    if (actionButton) {
      event.preventDefault();
      if (actionButton.disabled) return;
      const container = actionButton.closest("[data-document]");
      if (!container) return;

      actionButton.disabled = true;
      const body = new URLSearchParams({
        action: "spl_action",
        nonce: splData.nonce,
        document: container.dataset.document,
        kind: actionButton.dataset.spl,
      });

      try {
        const data = await request(body);
        if (data.reload) window.location.reload();
      } catch (error) {
        window.alert(error.message);
        actionButton.disabled = false;
      }
      return;
    }

    const submitButton = event.target.closest("[data-spl-form] button[type='submit']");
    if (!submitButton) return;
    event.preventDefault();
    if (submitButton.disabled) return;

    const form = submitButton.closest("[data-spl-form]");
    const container = form ? form.closest("[data-document]") : null;
    if (!form || !container) return;

    submitButton.disabled = true;
    const body = new FormData(form);
    body.append("action", "spl_action");
    body.append("nonce", splData.nonce);
    body.append("document", container.dataset.document);

    try {
      const data = await request(body);
      window.alert(data.message || "Saved.");
      form.reset();
    } catch (error) {
      window.alert(error.message);
    } finally {
      submitButton.disabled = false;
    }
  });
})();
