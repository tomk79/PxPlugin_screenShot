require 'capybara-webkit'

url = ARGV[0].to_s
path = ARGV[1].to_s
width = ARGV[2].to_i
height = ARGV[3].to_i

print 'screen shot ' + url + ' to ' + path + "\n"

if url.length <= 0 then
	print 'url NOT given.'+"\n"
	exit()
elsif path.length <= 0 then
	print 'path NOT given.'+"\n"
	exit()
elsif width <= 0 then
	print 'width NOT given.'+"\n"
	exit()
elsif height <= 0 then
	print 'height NOT given.'+"\n"
	exit()
end


browser = Capybara::Webkit::Driver.new('web_capture').browser
browser.visit url
browser.render(path, width, height)

print "exit()\n"
exit()
