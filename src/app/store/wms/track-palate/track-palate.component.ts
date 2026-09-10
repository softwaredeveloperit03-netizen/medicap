import { Component, OnInit, ViewChild, ElementRef, ChangeDetectorRef } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-track-palate',
  templateUrl: './track-palate.component.html',
  styleUrls: ['./track-palate.component.css']
})
export class TrackPalateComponent implements OnInit {

  barcodeInput: string = '';
  loading = false;
  palateData: any = null;
  materials: any[] = [];
  currentPage = 1;
  pageSize = 50;
  totalRecords = 0;

  @ViewChild('barcodeInputField', { static: false }) barcodeInputField: ElementRef;

  constructor(private service: DataAccessService, private cdr: ChangeDetectorRef) { }

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
        this.trackPalate(barcode);
      }
    }
  }

  trackPalate(barcode: string) {
    if (!barcode || barcode.trim() === '') {
      alertify.error('Please scan or enter palate barcode');
      return;
    }

    this.loading = true;
    this.palateData = null;
    this.materials = [];
    this.currentPage = 1;

    this.service.get('store/wms.php?type=trackPalate&barcode=' + encodeURIComponent(barcode) + '&page=' + this.currentPage + '&limit=' + this.pageSize).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.palateData = response['data']['palate'];
          this.materials = Array.isArray(response['data']['materials']) ? [...response['data']['materials']] : [];
          this.totalRecords = response['data']['total'] || 0;
          this.cdr.detectChanges();
          
          this.barcodeInput = '';
          setTimeout(() => {
            if (this.barcodeInputField) {
              this.barcodeInputField.nativeElement.focus();
            }
          }, 100);
        } else {
          console.log('Error Response:', response);
          alertify.error(response['message'] || 'Palate not found');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error tracking palate. Please try again.');
        console.error(error);
      }
    );
  }

  loadPage(page: number) {
    if (!this.palateData) return;
    this.currentPage = page;
    this.service.get('store/wms.php?type=trackPalate&barcode=' + encodeURIComponent(this.palateData.paletteNo) + '&page=' + this.currentPage + '&limit=' + this.pageSize).subscribe(
      (response) => {
        if (response && response['status'] === 'success') {
          this.materials = Array.isArray(response['data']['materials']) ? response['data']['materials'] : [];
          this.totalRecords = response['data']['total'] || 0;
          this.cdr.detectChanges();
        }
      }
    );
  }

  clearSearch() {
    this.barcodeInput = '';
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
