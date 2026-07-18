package com.hoteltv.manager

import android.content.Context
import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.grid.GridCells
import androidx.compose.foundation.lazy.grid.LazyVerticalGrid
import androidx.compose.foundation.lazy.grid.items
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Brush
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.tv.material3.*

class MainActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val prefs = getSharedPreferences("hoteltv_manager", Context.MODE_PRIVATE)
        setContent { HotelTvApp(prefs) }
    }
}

private enum class Screen { Login, Dashboard, Settings }

@Composable
private fun HotelTvApp(prefs: android.content.SharedPreferences) {
    val initiallyConfigured = prefs.getBoolean("configured", false)
    var screen by remember { mutableStateOf(if (initiallyConfigured) Screen.Dashboard else Screen.Login) }
    var serverUrl by remember { mutableStateOf(prefs.getString("server_url", "https://hoteltv-manager.mywire.org") ?: "") }
    var username by remember { mutableStateOf(prefs.getString("username", "") ?: "") }
    var localDatabase by remember { mutableStateOf(prefs.getBoolean("local_database", false)) }

    HotelTheme {
        when (screen) {
            Screen.Login -> LoginScreen(
                initialServerUrl = serverUrl,
                initialUsername = username,
                initialLocalDatabase = localDatabase,
                onContinue = { url, user, local ->
                    serverUrl = url.trim().trimEnd('/')
                    username = user.trim()
                    localDatabase = local
                    prefs.edit()
                        .putBoolean("configured", true)
                        .putString("server_url", serverUrl)
                        .putString("username", username)
                        .putBoolean("local_database", localDatabase)
                        .apply()
                    screen = Screen.Dashboard
                }
            )
            Screen.Dashboard -> DashboardScreen(
                username = username.ifBlank { "Administrator" },
                connectionLabel = if (localDatabase) "Local .hoteltv-manager.db" else serverUrl,
                onSettings = { screen = Screen.Settings }
            )
            Screen.Settings -> SettingsScreen(
                serverUrl = serverUrl,
                localDatabase = localDatabase,
                onBack = { screen = Screen.Dashboard },
                onReset = {
                    prefs.edit().clear().apply()
                    screen = Screen.Login
                }
            )
        }
    }
}

@Composable
private fun HotelTheme(content: @Composable () -> Unit) {
    val colors = darkColorScheme(
        primary = Color(0xFF42A5F5),
        onPrimary = Color.Black,
        surface = Color(0xFF10243A),
        onSurface = Color.White,
        background = Color(0xFF07111F),
        onBackground = Color.White
    )
    MaterialTheme(colorScheme = colors, content = content)
}

@Composable
private fun ScreenBackground(content: @Composable BoxScope.() -> Unit) {
    Box(
        modifier = Modifier
            .fillMaxSize()
            .background(
                Brush.linearGradient(
                    listOf(Color(0xFF07111F), Color(0xFF0C2740), Color(0xFF07111F))
                )
            )
            .padding(horizontal = 56.dp, vertical = 38.dp),
        content = content
    )
}

@Composable
private fun LoginScreen(
    initialServerUrl: String,
    initialUsername: String,
    initialLocalDatabase: Boolean,
    onContinue: (String, String, Boolean) -> Unit
) {
    var serverUrl by remember { mutableStateOf(initialServerUrl) }
    var username by remember { mutableStateOf(initialUsername) }
    var password by remember { mutableStateOf("") }
    var localDatabase by remember { mutableStateOf(initialLocalDatabase) }

    ScreenBackground {
        Row(Modifier.fillMaxSize(), verticalAlignment = Alignment.CenterVertically) {
            Column(Modifier.weight(1f).padding(end = 64.dp)) {
                Icon(Icons.Default.Tv, null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(74.dp))
                Spacer(Modifier.height(18.dp))
                Text("HotelTV Manager", fontSize = 42.sp, fontWeight = FontWeight.Bold)
                Text("Android TV administration console", fontSize = 20.sp, color = Color(0xFFB8CEE2))
                Spacer(Modifier.height(28.dp))
                Text("Manage properties, rooms, televisions, playlists and device status from the TV-friendly dashboard.", fontSize = 18.sp, lineHeight = 27.sp)
            }

            Surface(modifier = Modifier.width(530.dp), shape = MaterialTheme.shapes.large) {
                Column(Modifier.padding(32.dp), verticalArrangement = Arrangement.spacedBy(17.dp)) {
                    Text("Connect", fontSize = 30.sp, fontWeight = FontWeight.Bold)
                    Text("Choose the hosted server or a local database.", color = Color(0xFFB8CEE2))
                    Button(onClick = { localDatabase = !localDatabase }, modifier = Modifier.fillMaxWidth()) {
                        Icon(if (localDatabase) Icons.Default.Storage else Icons.Default.Cloud, null)
                        Spacer(Modifier.width(12.dp))
                        Text(if (localDatabase) "Local database mode" else "Hosted server mode")
                    }
                    if (!localDatabase) {
                        androidx.compose.material3.OutlinedTextField(
                            value = serverUrl,
                            onValueChange = { serverUrl = it },
                            label = { androidx.compose.material3.Text("Server URL") },
                            singleLine = true,
                            keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Uri),
                            modifier = Modifier.fillMaxWidth()
                        )
                    }
                    androidx.compose.material3.OutlinedTextField(
                        value = username,
                        onValueChange = { username = it },
                        label = { androidx.compose.material3.Text("Username") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth()
                    )
                    androidx.compose.material3.OutlinedTextField(
                        value = password,
                        onValueChange = { password = it },
                        label = { androidx.compose.material3.Text("Password") },
                        singleLine = true,
                        visualTransformation = PasswordVisualTransformation(),
                        modifier = Modifier.fillMaxWidth()
                    )
                    Button(
                        onClick = { onContinue(serverUrl, username, localDatabase) },
                        enabled = localDatabase || serverUrl.isNotBlank(),
                        modifier = Modifier.fillMaxWidth()
                    ) {
                        Text("Open Manager")
                        Spacer(Modifier.width(10.dp))
                        Icon(Icons.Default.ArrowForward, null)
                    }
                    Text("Initial scaffold: authentication API verification will be connected in the next stage.", fontSize = 13.sp, color = Color(0xFF90A8BC))
                }
            }
        }
    }
}

data class DashboardItem(val title: String, val subtitle: String, val icon: ImageVector)

@Composable
private fun DashboardScreen(username: String, connectionLabel: String, onSettings: () -> Unit) {
    val items = listOf(
        DashboardItem("Properties", "Create and manage hotel sites", Icons.Default.Business),
        DashboardItem("Rooms", "Assign rooms and occupancy details", Icons.Default.MeetingRoom),
        DashboardItem("Devices", "Provision and monitor televisions", Icons.Default.Tv),
        DashboardItem("Playlists", "Manage channels and content", Icons.Default.PlaylistPlay),
        DashboardItem("Messages", "Send notices to guest screens", Icons.Default.Campaign),
        DashboardItem("System status", "Review connectivity and health", Icons.Default.MonitorHeart)
    )
    ScreenBackground {
        Column(Modifier.fillMaxSize()) {
            Row(Modifier.fillMaxWidth(), verticalAlignment = Alignment.CenterVertically) {
                Column(Modifier.weight(1f)) {
                    Text("HotelTV Manager", fontSize = 36.sp, fontWeight = FontWeight.Bold)
                    Text("Welcome, $username", fontSize = 19.sp, color = Color(0xFFB8CEE2))
                }
                Button(onClick = onSettings) {
                    Icon(Icons.Default.Settings, null)
                    Spacer(Modifier.width(10.dp))
                    Text("Settings")
                }
            }
            Spacer(Modifier.height(16.dp))
            Text(connectionLabel, fontSize = 14.sp, color = MaterialTheme.colorScheme.primary)
            Spacer(Modifier.height(26.dp))
            LazyVerticalGrid(
                columns = GridCells.Fixed(3),
                horizontalArrangement = Arrangement.spacedBy(22.dp),
                verticalArrangement = Arrangement.spacedBy(22.dp),
                modifier = Modifier.fillMaxSize()
            ) {
                items(items) { item ->
                    Card(onClick = {}, modifier = Modifier.height(190.dp)) {
                        Column(Modifier.fillMaxSize().padding(24.dp), verticalArrangement = Arrangement.Center) {
                            Icon(item.icon, null, tint = MaterialTheme.colorScheme.primary, modifier = Modifier.size(42.dp))
                            Spacer(Modifier.height(18.dp))
                            Text(item.title, fontSize = 24.sp, fontWeight = FontWeight.Bold)
                            Spacer(Modifier.height(7.dp))
                            Text(item.subtitle, color = Color(0xFFB8CEE2), fontSize = 15.sp)
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun SettingsScreen(serverUrl: String, localDatabase: Boolean, onBack: () -> Unit, onReset: () -> Unit) {
    ScreenBackground {
        Column(Modifier.fillMaxSize()) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Button(onClick = onBack) { Icon(Icons.Default.ArrowBack, null); Spacer(Modifier.width(8.dp)); Text("Back") }
                Spacer(Modifier.width(24.dp))
                Text("Settings", fontSize = 36.sp, fontWeight = FontWeight.Bold)
            }
            Spacer(Modifier.height(38.dp))
            Surface(shape = MaterialTheme.shapes.large, modifier = Modifier.fillMaxWidth()) {
                Column(Modifier.padding(30.dp), verticalArrangement = Arrangement.spacedBy(15.dp)) {
                    Text("Connection", fontSize = 24.sp, fontWeight = FontWeight.Bold)
                    Text(if (localDatabase) "Local database: .hoteltv-manager.db" else serverUrl, color = Color(0xFFB8CEE2))
                    Text("Version 0.1.0", color = Color(0xFF90A8BC))
                    Spacer(Modifier.height(10.dp))
                    Button(onClick = onReset) {
                        Icon(Icons.Default.RestartAlt, null)
                        Spacer(Modifier.width(10.dp))
                        Text("Reset configuration")
                    }
                }
            }
        }
    }
}
