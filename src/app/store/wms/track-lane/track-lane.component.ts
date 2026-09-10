import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-track-lane',
  templateUrl: './track-lane.component.html',
  styleUrls: ['./track-lane.component.css']
})
export class TrackLaneComponent implements OnInit {

  barcodeInput: string = '';
  loading = false;
  laneData: any = null;
  racks: any[] = [];
  currentPage = 1;
  pageSize = 50;
  totalRecords = 0;
  summary: any = { totalLocations: 0, totalPalates: 0, totalMaterials: 0 };

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
        this.trackLane(barcode);
      }
    }
  }

  trackLane(barcode: string) {
    if (!barcode || barcode.trim() === '') {
      alertify.error('Please scan or enter lane barcode');
      return;
    }

    this.loading = true;
    this.laneData = null;
    this.racks = [];
    this.currentPage = 1;

    this.service.get('store/wms.php?type=trackLane&barcode=' + encodeURIComponent(barcode) + '&page=' + this.currentPage + '&limit=' + this.pageSize).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.laneData = response['data']['lane'];
          this.racks = Array.isArray(response['data']['racks']) ? response['data']['racks'] : [];
          this.totalRecords = response['data']['total'] || 0;
          this.summary = response['data']['summary'] || { totalLocations: 0, totalPalates: 0, totalMaterials: 0 };
          console.log('Lane Data:', this.laneData);
          console.log('Racks:', this.racks);
          console.log('Total Records:', this.totalRecords);
          console.log('Summary:', this.summary);
          this.barcodeInput = '';
          setTimeout(() => {
            if (this.barcodeInputField) {
              this.barcodeInputField.nativeElement.focus();
            }
          }, 100);
        } else {
          alertify.error(response['message'] || 'Lane not found');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error tracking lane. Please try again.');
        console.error(error);
      }
    );
  }

  loadPage(page: number) {
    if (!this.laneData) return;
    this.currentPage = page;
    this.service.get('store/wms.php?type=trackLane&barcode=' + encodeURIComponent(this.laneData.laneNO) + '&page=' + this.currentPage + '&limit=' + this.pageSize).subscribe(
      (response) => {
        if (response && response['status'] === 'success') {
          this.racks = Array.isArray(response['data']['racks']) ? response['data']['racks'] : [];
          this.totalRecords = response['data']['total'] || 0;
        }
      }
    );
  }

  clearSearch() {
    this.barcodeInput = '';
    this.laneData = null;
    this.racks = [];
    this.summary = { totalLocations: 0, totalPalates: 0, totalMaterials: 0 };
    setTimeout(() => {
      if (this.barcodeInputField) {
        this.barcodeInputField.nativeElement.focus();
      }
    }, 100);
  }

}
