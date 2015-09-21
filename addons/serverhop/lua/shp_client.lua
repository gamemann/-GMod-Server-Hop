local ServerList = {}
local Window = nil
local WindowPage = 1
local WindowPages = 1
local WindowLabels = {}

local KeyDelay = 0.25
local KeyLimit = nil

local GUIColor = GUIColor or {
	LightGray = Color(52, 73, 94),
	DarkGray = Color(44, 62, 80),
	Blue = Color(0, 120, 255),
	White = Color(255, 255, 255)
}

surface.CreateFont( "HUDTitle", { size = 24, font = "Coolvetica" } )
surface.CreateFont( "HUDLabel", { size = 17, weight = 550, font = "Verdana" } )


local function CreateMenu()
	KeyLimit = nil

	Window = vgui.Create( "DFrame" )
	Window:SetTitle( "" )
	Window:SetDraggable( false )
	Window:ShowCloseButton( false )
	
	Window:SetSize( 400, 270 )
	Window:SetPos( 20, ScrH() / 2 - Window:GetTall() / 2 )
	
	WindowPage = 1
	WindowPages = math.ceil( #ServerList / 7 )
	
	Window.Think = function()
		local Key = -1
		for KeyID = 1, 10 do
			if input.IsKeyDown( KeyID ) then
				Key = KeyID - 1
				break
			end
		end
		
		if Key > 0 and Key < 8 and not KeyLimit then
			local Item = ServerList[ 7 * WindowPage - 7 + Key ]
			if Item then
				Derma_Query("Join " .. Item.Name .. " on " .. Item.Map .. "?", "Join Server?", "Yes", function() if LocalPlayer() and IsValid( LocalPlayer() ) then LocalPlayer():ConCommand( "connect " .. Item.IP ) end end, "No", function() end)
				Key = 0
			end
		elseif Key == 8 and not KeyLimit and WindowPage != 1 then
			WindowPage = WindowPage - 1
			
			local Index = 7 * WindowPage - 7
			local Offset = 35
			for i = 1, 7 do
				local Data = ServerList[ Index + i ]
				if Data then
					WindowLabels[i]:SetText( i .. ". " .. Data.Name .. " (" .. Data.Players .. " / " .. Data.PlayersMax .. ") on " .. Data.Map )
					WindowLabels[i]:SizeToContents()
					WindowLabels[i]:SetVisible( true )
					
					Offset = Offset + 20
				else
					WindowLabels[i]:SetText( "" )
					WindowLabels[i]:SizeToContents()
					WindowLabels[i]:SetVisible( false )
				end
			end
			
			if WindowPage == 1 then
				WindowLabels[8]:SetVisible( false )
				WindowLabels[9]:SetVisible( true )
			else
				WindowLabels[8]:SetVisible( true )
				WindowLabels[9]:SetVisible( true )
			end
		elseif Key == 9 and not KeyLimit and WindowPage != WindowPages then
			WindowPage = WindowPage + 1
			
			local Index = 7 * WindowPage - 7
			local Offset = 35
			for i = 1, 7 do
				local Data = ServerList[ Index + i ]
				if Data then
					WindowLabels[i]:SetText( i .. ". " .. Data.Name .. " (" .. Data.Players .. " / " .. Data.PlayersMax .. ") on " .. Data.Map )
					WindowLabels[i]:SizeToContents()
					WindowLabels[i]:SetVisible( true )
					
					Offset = Offset + 20
				else
					WindowLabels[i]:SetText( "" )
					WindowLabels[i]:SizeToContents()
					WindowLabels[i]:SetVisible( false )
				end
			end
		
			if WindowPage == WindowPages then
				WindowLabels[8]:SetVisible( true )
				WindowLabels[9]:SetVisible( false )
			else
				WindowLabels[8]:SetVisible( true )
				WindowLabels[9]:SetVisible( true )
			end
		end

		if Key == 0 and not KeyLimit then
			KeyLimit = true
			timer.Simple( KeyDelay, function()
				KeyLimit = nil
				if IsValid( Window ) then
					Window:Close()
					Window = nil
				end
			end )
		elseif Key >= 0 and not KeyLimit then
			KeyLimit = true
			timer.Simple( KeyDelay, function()
				KeyLimit = nil
			end )
		end
	end
	
	Window.Paint = function()
		local w, h = Window:GetWide(), Window:GetTall()
		surface.SetDrawColor( GUIColor.DarkGray )
		surface.DrawRect( 0, 0, w, h )
		surface.SetDrawColor( GUIColor.LightGray )
		surface.DrawRect( 10, 30, w - 20, h - 40 )
		draw.SimpleText( "Server List (#" .. #ServerList .. ")", "HUDTitle", 10, 5, GUIColor.Blue, TEXT_ALIGN_LEFT )
	end
	
	local Offset = 35
	for i = 1, 7 do
		local Data = ServerList[i]
		if Data then
			WindowLabels[i] = MakeLabel{ parent = Window, x = 15, y = Offset, font = "HUDLabel", color = GUIColor.White, text = i .. ". " .. Data.Name .. " (" .. Data.Players .. " / " .. Data.PlayersMax .. ") on " .. Data.Map }
			Offset = Offset + 20
		end
	end
	
	Offset = 35 + (8 * 20)
	WindowLabels[8] = MakeLabel{ parent = Window, x = 15, y = Offset, font = "HUDLabel", color = GUIColor.White, text = "8. Previous Page" }
	WindowLabels[9] = MakeLabel{ parent = Window, x = 15, y = Offset + 20, font = "HUDLabel", color = GUIColor.White, text = "9. Next Page" }
	WindowLabels[10] = MakeLabel{ parent = Window, x = 15, y = Offset + 40, font = "HUDLabel", color = GUIColor.White, text = "0. Close" }
	
	WindowLabels[8]:SetVisible( false )
	if #ServerList < 8 then
		WindowLabels[9]:SetVisible( false )
	end
end


local function ReceiveMenu()
	ServerList = net.ReadTable() or {}
	
	if IsValid( Window ) then
		Window:Show()
	else
		CreateMenu()
	end
end
net.Receive("SendServersMenu", ReceiveMenu)

local function ReceiveAdvert()
	local r = net.ReadTable()
	if not r then return end
	
	chat.AddText(Color(255, 0, 0), r.Name, Color(255, 255, 255), " (" .. r.Players .. " / " .. r.PlayersMax .. ") on map ", Color(0, 0, 255), r.Map, Color(255, 255, 255), "! Type !join to join!")
end
net.Receive("Adverts", ReceiveAdvert)

function MakeLabel( t )
	local lbl = vgui.Create( "DLabel", t.parent )
	lbl:SetPos( t.x, t.y )
	lbl:SetFont( t.font )
	lbl:SetColor( t.color )
	lbl:SetText( t.text )
	lbl:SizeToContents()
	return lbl
end