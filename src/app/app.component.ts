import { Component, OnInit, Inject } from '@angular/core';
import { DataAccessService } from './data-access.service';
import { AppThemeService } from './shared/app-theme.service';
import { SessionSecurityService } from './shared/session-security/session-security.service';
import { environment } from '../environments/environment';

interface City {
  name: string,
  code: string
}

@Component({
  selector: 'app-root',
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.css']
})

export class AppComponent implements OnInit {

  constructor(
    @Inject(DataAccessService) private service: DataAccessService,
    @Inject(AppThemeService) private appTheme: AppThemeService,
    @Inject(SessionSecurityService) private sessionSecurity: SessionSecurityService
  ) {}

  ngOnInit() {
    this.service.syncServerConnection();
    if (environment.deployEnv === 'live') {
      this.service.pingServerHealth().subscribe((h) => {
        if (h?.status !== 'ok') {
          console.warn('[Medicap] Server health check:', h);
        }
      });
    }
    this.appTheme.applySavedTheme();
    this.sessionSecurity.init();
  }
}
