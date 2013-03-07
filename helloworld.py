import webapp2

class hello(webapp2.RequestHandler):
  def get(self):
      self.response.headers['Content-Type'] = 'text/plain'
      self.response.write('Welcome to my MLB Data Analysis App!')

app = webapp2.WSGIApplication([('/hello', hello)],
                              debug=True)
