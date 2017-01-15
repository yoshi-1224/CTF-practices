##Massive Fail

This challenge is from PicoCTFF 2014. We are provided with a webpage for user registration written in Ruby. On the webpage itself, there are 3 input fields: 
name, username and password. We are also given the source code for this webpage as well, in which we are supposed to identify the vulnerability.

For this challenge, there are two essential source code files for exploitation: ```db/schema.rb``` and ```app/controller/user_controller.rb```.
See the following for the contents inside the two source codes respectively:

```
# encoding: UTF-8
# This file is auto-generated from the current state of the database. Instead
# of editing this file, please use the migrations feature of Active Record to
# incrementally modify your database, and then regenerate this schema definition.
#
# Note that this schema.rb definition is the authoritative source for your
# database schema. If you need to create the application database on another
# system, you should be using db:schema:load, not running all the migrations
# from scratch. The latter is a flawed and unsustainable approach (the more migrations
# you'll amass, the slower it'll run and the greater likelihood for issues).
#
# It's strongly recommended to check this file into your version control system.

ActiveRecord::Schema.define(:version => 20141008175655) do

  create_table "users", :force => true do |t|
    t.string   "username"
    t.string   "password"
    t.string   "name"
    t.boolean  "is_admin"
    t.datetime "created_at"
    t.datetime "updated_at"
  end

end
```

```
class UserController < ApplicationController
  def register     
  end

  def create
    # User.new creates a new user according to ALL the parameters
    @new_user = User.new(params[:user])
    @new_user.save
  end
end
```

See the comment ```User.new creates a new user according to ALL the parameters```. This basically means that all the parameters that we pass through the form gets directly saved in the database. Also, check the html file 
```app/views/user/create.html``` with the following code inside:

```
<h1>Registration Complete!</h1>
<h3>Welcome <%= @new_user.name %>!</h3>
<% if (@new_user.is_admin) then %>
  <p>You're an admin, so you get to know the secret code: <b>flag removed</b></p>
<% else %>
  <p>You're not an administrator so you get no secrets.</p>
<% end %>
```
So our goal is to set the flag ```is_admin``` to True/1. Note that since no value is inserted into the database, the flag defaults to False/0.
In the end, all we needed to do was explicity set ```is_admin``` to 1. This could be achieved by directly modifying the post request, or on browser edit the HTML to make another field for ```is_admin```.
I took the latter method, so basically copy and paste one of the input fields, then change as the following:
```
<input id="is_admin" name="user[is_admin]" size="30" type="text"  value = "1"/>
```
click on register, and on the create page shows ```no_framework_is_without_sin```


