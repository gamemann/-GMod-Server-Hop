if SERVER then
	AddCSLuaFile( "shp_client.lua" )
	include( "shp_server.lua" )
elseif CLIENT then
	include( "shp_client.lua" )
end