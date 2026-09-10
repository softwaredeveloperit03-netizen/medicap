import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-rack-location-view',
  templateUrl: './rack-location-view.component.html',
  styleUrls: ['./rack-location-view.component.css']
})
export class RackLocationViewComponent implements OnInit {

  sections: any;
  lanes: any;
  racks: any;
  locations: any;
  
  selectedSection: string = '';
  selectedLane: string = '';
  selectedRack: string = '';
  
  loading = false;
  currentPage = 1;
  pageSize = 100;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSections();
  }

  getSections() {
    this.service.get('store/location.php?type=getSections').subscribe(
      (response) => {
        this.sections = response || [];
      }
    );
  }

  getLanesBySection() {
    if (!this.selectedSection) {
      this.lanes = [];
      this.racks = [];
      this.locations = [];
      return;
    }
    this.service.get('store/location.php?type=getLaneBySection&section_name=' + encodeURIComponent(this.selectedSection)).subscribe(
      (response) => {
        this.lanes = response || [];
        this.racks = [];
        this.locations = [];
      }
    );
  }

  getRacksByLane() {
    if (!this.selectedLane) {
      this.racks = [];
      this.locations = [];
      return;
    }
    this.service.get('store/location.php?type=getRacksByLane&laneNO=' + encodeURIComponent(this.selectedLane)).subscribe(
      (response) => {
        this.racks = response || [];
        this.locations = [];
      }
    );
  }

  loadRackLocations() {
    if (!this.selectedRack) {
      this.locations = [];
      return;
    }

    this.loading = true;
    this.service.get('store/wms.php?type=getRackLocationStatus&rack_barcode=' + encodeURIComponent(this.selectedRack) + '&page=' + this.currentPage + '&limit=' + this.pageSize).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.locations = response['data']['locations'] || [];
        } else {
          alertify.error(response['message'] || 'Failed to load locations');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error loading locations. Please try again.');
        console.error(error);
      }
    );
  }

  getLocationColor(location: any): string {
    return location.occupied ? '#dc3545' : '#28a745'; // Red for occupied, Green for empty
  }

  getLocationStatus(location: any): string {
    return location.occupied ? 'Occupied' : 'Empty';
  }

  viewLocationDetails(location: any) {
    if (location.occupied) {
      alertify.alert('Location Details', 
        'Location: ' + location.locationNo + '<br>' +
        'Status: Occupied<br>' +
        'Palate: ' + location.palate_barcode + '<br>' +
        'Materials: ' + (location.materials_count || 0)
      );
    } else {
      alertify.alert('Location Details', 
        'Location: ' + location.locationNo + '<br>' +
        'Status: Empty'
      );
    }
  }

}
