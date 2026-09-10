import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-palate-status-view',
  templateUrl: './palate-status-view.component.html',
  styleUrls: ['./palate-status-view.component.css']
})
export class PalateStatusViewComponent implements OnInit {

  palates: any[] = [];
  loading = false;
  currentPage = 1;
  pageSize = 100;
  totalRecords = 0;
  sectionFilter: string = '';

  sections: any;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSections();
    this.loadPalates();
  }

  getSections() {
    this.service.get('store/location.php?type=getSections').subscribe(
      (response) => {
        this.sections = response || [];
      }
    );
  }

  loadPalates() {
    this.loading = true;
    let url = 'store/wms.php?type=getPalateStatus&page=' + this.currentPage + '&limit=' + this.pageSize;
    if (this.sectionFilter) {
      url += '&section_name=' + encodeURIComponent(this.sectionFilter);
    }
    
    this.service.get(url).subscribe(
      (response) => {
        this.loading = false;
        console.log('Full Response:', response);
        if (response && response['status'] === 'success') {
          this.palates = Array.isArray(response['data']['palates']) ? response['data']['palates'] : [];
          this.totalRecords = response['data']['total'] || 0;
          console.log('Palates Array:', this.palates);
          console.log('Palates Length:', this.palates.length);
          console.log('Total Records:', this.totalRecords);
          if (this.palates.length === 0 && this.totalRecords > 0) {
            alertify.warning('No palates found for current page. Try changing page size.');
          }
        } else {
          console.log('Error Response:', response);
          alertify.error(response['message'] || 'Failed to load palates');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error loading palates. Please try again.');
        console.error('Error:', error);
      }
    );
  }

  loadPage(page: number) {
    this.currentPage = page;
    this.loadPalates();
  }

  filterBySection() {
    this.currentPage = 1;
    this.loadPalates();
  }

  getPalateColor(palate: any): string {
    const hasMaterials = palate.has_materials === true || palate.has_materials === 1 || palate.has_materials === '1';
    const atLocation = palate.at_location === true || palate.at_location === 1 || palate.at_location === '1';
    
    if (hasMaterials && atLocation) {
      return '#dc3545'; // Red - occupied with materials
    } else if (hasMaterials) {
      return '#ffc107'; // Yellow - has materials but not at location
    } else if (atLocation) {
      return '#17a2b8'; // Blue - at location but empty
    } else {
      return '#28a745'; // Green - empty and not at location
    }
  }

  getPalateStatus(palate: any): string {
    const hasMaterials = palate.has_materials === true || palate.has_materials === 1 || palate.has_materials === '1';
    const atLocation = palate.at_location === true || palate.at_location === 1 || palate.at_location === '1';
    
    if (hasMaterials && atLocation) {
      return 'Occupied';
    } else if (hasMaterials) {
      return 'Has Materials';
    } else if (atLocation) {
      return 'At Location';
    } else {
      return 'Empty';
    }
  }

  viewPalateDetails(palate: any) {
    let details = 'Palate: ' + palate.paletteNo + '<br>' +
                  'Room name: ' + palate.section_name + '<br>' +
                  'Status: ' + this.getPalateStatus(palate) + '<br>' +
                  'Materials Count: ' + (palate.materials_count || 0) + '<br>';
    
    if (palate.location) {
      details += 'Location: ' + palate.location + '<br>';
    }
    
    alertify.alert('Palate Details', details);
  }

}
