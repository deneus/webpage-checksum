/**
 * @file
 * This file was generated with ChatGPT.
 */
const {spawn} = require("child_process");
const core = require("@actions/core");

function execDocker(args, cwd) {
  return new Promise((resolve, reject) => {
    const proc = spawn("docker", args, {cwd, stdio: "inherit", shell: false});
    proc.on("close", (code) => code === 0 ? resolve() : reject(new Error(`docker ${args.join(" ")} exited with ${code}`)));
    proc.on("error", reject);
  });
}

async function cleanup() {
  try {
    const commonArgs = JSON.parse(core.getState("commonArgsJSON") || "[]");
    const wd = core.getState("workingDir") || undefined;
    const downArgs = ["compose", ...commonArgs, "down"];

    core.info(`Post-job cleanup: docker ${downArgs.join(" ")}`);
    await execDocker(downArgs, wd);
  } catch (err) {
    // Don't fail the whole job because cleanup struggled; just warn.
    core.warning(`Cleanup failed: ${err.message || String(err)}`);
  }
}

cleanup();
