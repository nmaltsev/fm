## API actions
()[http://localhost:9000/terminal.php?action=read&f=2&to=64&r=/home/nmaltsev/Documents/repos/desk/notes.txt]

You can access the output via the proc filesystem.

tail -f /proc/<pid>/fd/1
1 = stdout, 2 = stderr

(or like @jmhostalet says: cat /proc/<pid>/fd/1 if tail doesn't work)
----------------------

If all you want to do is spy on the existing process, you can use strace -p1234 -s9999 -e write where 1234 is the process ID. (-s9999 avoids having strings truncated to 32 characters, and write the system call that produces output.) If you want to view only data written on a particular file descriptor, you can use something like strace -p1234 -e trace= -e write=3 to see only data written to file descriptor 3 (-e trace= prevents the system calls from being loged). That won't give you output that's already been produced.

If the output is scrolling by too fast, you can pipe it into a pager such as less, or send it to a file with strace -o trace.log ….
