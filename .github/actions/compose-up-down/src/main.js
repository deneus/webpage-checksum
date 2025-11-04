/**
 * @file
 * This file was generated with ChatGPT.
 */
const {spawn} = require("child_process");
const core = require("@actions/core");

function splitList(s) {
  if (!s) return [];
  return s.split(/[,\s]+/).map(x => x.trim()).filter(Boolean);
}

function buildCommonArgs({composeFiles, projectName, profiles}) {
  const args = [];
  for (const f of composeFiles) args.push("-f", f);
  if (projectName) args.push("-p", projectName);
  for (const p of profiles) args.push("--profile", p);
  return args;
}

async function run() {
  try {
    const wdInput = core.getInput("working-directory");
    const composeFiles = splitList(core.getInput("compose-file"));
    const profiles = splitList(core.getInput("profiles"));
    const projectName = core.getInput("project-name");
    const noWait = /^true$/i.test(core.getInput("no-wait"));

    const commonArgs = buildCommonArgs({composeFiles, projectName, profiles});
    const upArgs = ["compose", ...commonArgs, "up", "--detach"];
    if (!noWait) upArgs.push("--wait");

    // Save state for post step
    core.saveState("commonArgsJSON", JSON.stringify(commonArgs));
    core.saveState("workingDir", wdInput);

    core.info(`Running: docker ${upArgs.join(" ")}`);
    await execDocker(upArgs, wdInput || undefined);
  } catch (err) {
    core.setFailed(err.message || String(err));
  }
}

function execDocker(args, cwd) {
  return new Promise((resolve, reject) => {
    const proc = spawn("docker", args, {cwd, stdio: "inherit", shell: false});
    proc.on("close", (code) => code === 0 ? resolve() : reject(new Error(`docker ${args.join(" ")} exited with ${code}`)));
    proc.on("error", reject);
  });
}

run();
