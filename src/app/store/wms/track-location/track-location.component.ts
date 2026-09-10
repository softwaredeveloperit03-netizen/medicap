import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-track-location',
  templateUrl: './track-location.component.html',
  styleUrls: ['./track-location.component.css']
})
export class TrackLocationComponent implements OnInit {

  barcodeInput: string = '';
  loading = false;
  locationData: any = null;
  palateData: any = null;
  materials: any[] = [];
  currentPage = 1;
  pageSize = 50;
  totalRecords = 0;

  @ViewChild('barcodeInputField', { static: false }) barcodeInputField: ElementRef;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    setTimeout(() => {
      if (this.barcodeInputField) {
        this.barcodeInputField.nativeElement.focus();
      }
    }, 100);
  }

  onBarcodeScan(event: KeyboardEvent) {
    if (event.key === 'Enter') {
      const barcode = this.barcodeInput.trim();
      if (barcode) {
        this.trackLocation(barcode);
      }
    }
  }

  trackLocation(barcode: string) {
    if (!barcode || barcode.trim() === '') {
      alertify.error('Please scan or enter location barcode');
      return;
    }

    this.loading = true;
    this.locationData = null;
    this.palateData = null;
    this.materials = [];
    this.currentPage = 1;

    this.service.get('store/wms.php?type=trackLocation&barcode=' + encodeURIComponent(barcode) + '&page=' + this.currentPage + '&limit=' + this.pageSize).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.locationData = response['data']['location'];
          this.palateData = response['data']['palate'];
          this.materials = response['data']['materials'] || [];
          this.totalRecords = response['data']['total'] || 0;
          this.barcodeInput = '';
          setTimeout(() => {
            if (this.barcodeInputField) {
              this.barcodeInputField.nativeElement.focus();
            }
          }, 100);
        } else {
          alertify.error(response['message'] || 'Location not found');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error tracking location. Please try again.');
        console.error(error);
      }
    );
  }

  loadPage(page: number) {
    if (!this.locationData) return;
    this.currentPage = page;
    this.service.get('store/wms.php?type=trackLocation&barcode=' + encodeURIComponent(this.locationData.locationNo) + '&page=' + this.currentPage + '&limit=' + this.pageSize).subscribe(
      (response) => {
        if (response && response['status'] === 'success') {
          this.materials = response['data']['materials'] || [];
          this.totalRecords = response['data']['total'] || 0;
        }
      }
    );
  }

  clearSearch() {
    this.barcodeInput = '';
    this.locationData = null;
    this.palateData = null;
    this.materials = [];
    setTimeout(() => {
      if (this.barcodeInputField) {
        this.barcodeInputField.nativeElement.focus();
      }
    }, 100);
  }

  getStatusColor(status: string): string {
    if (!status) return '#6c757d'; // Gray for N/A
    const statusLower = status.toLowerCase();
    if (statusLower.includes('quarantine')) {
      return '#17a2b8'; // Blue
    } else if (statusLower.includes('undertest') || statusLower.includes('under test')) {
      return '#ffc107'; // Yellow
    } else if (statusLower.includes('approved')) {
      return '#28a745'; // Green
    }
    return '#6c757d'; // Default gray
  }

}
