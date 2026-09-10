import { Component, OnInit, OnDestroy } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit, OnDestroy {
  liveActivities: any[] = [];
  loading = false;
  stats: any = {
    totalMaterials: 0,
    totalPalates: 0,
    totalLocations: 0,
    activeMappings: 0
  };
  private refreshInterval: any;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.loadLiveStatus();
    this.loadStats();
    // Refresh every 5 seconds for more real-time updates
    this.refreshInterval = setInterval(() => {
      this.loadLiveStatus();
      this.loadStats();
    }, 5000);
  }

  ngOnDestroy() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval);
    }
  }

  loadLiveStatus() {
    this.loading = true;
    this.service.get('store/wms.php?type=getLiveStatus&limit=10').subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.liveActivities = response['data']['activities'] || [];
          // Keep only last 10 items
          if (this.liveActivities.length > 10) {
            this.liveActivities = this.liveActivities.slice(0, 10);
          }
          console.log('Live activities loaded:', this.liveActivities);
        } else {
          console.log('Live status response:', response);
        }
      },
      (error) => {
        this.loading = false;
        console.error('Error loading live status:', error);
      }
    );
  }

  loadStats() {
    this.service.get('store/wms.php?type=getWMSStats').subscribe(
      (response) => {
        if (response && response['status'] === 'success') {
          this.stats = response['data'] || this.stats;
        }
      },
      (error) => {
        console.error('Error loading stats:', error);
      }
    );
  }

  getActivityIcon(action: string): string {
    const icons: any = {
      'map': 'fas fa-link',
      'empty': 'fas fa-unlink',
      'track': 'fas fa-search',
      'material': 'fas fa-barcode',
      'palate': 'fas fa-pallet',
      'location': 'fas fa-map-marker-alt'
    };
    return icons[action] || 'fas fa-info-circle';
  }

}
