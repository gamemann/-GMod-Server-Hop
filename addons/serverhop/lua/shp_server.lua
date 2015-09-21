-- Config
local GameID = 1 -- Default: Garry's Mod
local locationID = 1 -- Default: United States
local dbName = "serverlist" -- The database name.

-- Global (local) Variables
local ServerList = {}
local RandomServer = nil
local Connected = false

require("mysqloo")
util.AddNetworkString("SendServersMenu");
util.AddNetworkString("Adverts");

local db = mysqloo.connect("HOST", "DBUSER", "DBPASS", dbName, 3306)
	
function db:onConnected(bRefresh)
	local query = db:query("SELECT * FROM `servers` WHERE `gameid`=" .. GameID .. " AND `location`=" .. locationID)
	function query:onSuccess(data)
		Connected = true
		ServerList = {}
	
		for _,tab in pairs(data) do
			if tonumber( tab.playersmax ) == 0 then continue end
			table.insert( ServerList, {
				ID = tab.id,
				Name = tab.name,
				IP = tab.publicip,
				Port = tab.port,
				Players = tab.players,
				PlayersMax = tab.playersmax,
				Map = tab.map,
			} )
		end
		
		if bRefresh then
			AdvertTick()
		end
	end
		
	function query:onError (err)
		Msg("Error with selecting the Server Hop servers!\n")
		Msg("[SQL Error]: " .. err .. "\n")
		
		Connected = false
	end
		
	query:start()
end

function db:onConnectionFailed (err)
	Msg("Error connecting to the Server Hop database! \n")
	Msg("[SQL Error]: " .. err .. "\n")
		
	Connected = false
end

db:connect()

local function HookSay(ply, text)
	if text == "!serverhop" or text == "/serverhop" or text == "!servers" or text == "/servers" or text == "!hop" or text == "/hop" then
		if Connected and #ServerList > 0 then
			net.Start("SendServersMenu")
			net.WriteTable(ServerList)
			net.Send(ply)
		else
			ply:ChatPrint("Server Hop isn't available right now!")
		end
		
		return ""
	elseif text == "!join" or text == "/join" then
		if RandomServer then
			ply:SendLua("Derma_Query('Join " .. RandomServer.Name .. " on " .. RandomServer.Map .. "?', 'Join Server?', 'Yes', function() if LocalPlayer() and IsValid( LocalPlayer() ) then LocalPlayer():ConCommand( 'connect " .. RandomServer.IP .. "' ) end end, 'No', function() end)")
		else
			ply:ChatPrint("There is no server available to join.")
		end
		
		return ""
	end
end
hook.Add("PlayerSay", "ChatCommands", HookSay)

function AdvertTick()
	local ServerSelect = nil
	if #ServerList > 0 then
		for _, data in RandomPairs( ServerList ) do
			if data.PlayersMax != 0 then
				ServerSelect = data
			end
		end
	end
	
	if ServerSelect then
		RandomServer = ServerSelect
	
		net.Start("Adverts")
		net.WriteTable(RandomServer)
		net.Broadcast()
	end
end
timer.Create("Adverts", 90, 0, function() db:onConnected(true) end)