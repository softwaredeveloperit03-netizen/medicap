import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-track-rack',
  templateUrl: './track-rack.component.html',
  styleUrls: ['./track-rack.component.css']
})
export class TrackRackComponent implements OnInit {

  barcodeInput: string = '';
  loading = false;
  rackData: any = null;
  locations: any[] = [];
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
        this.trackRack(barcode);
      }
    }
  }

  trackRack(barcode: string) {
    if (!barcode || barcode.trim() === '') {
      alertify.error('Please scan or enter rack barcode');
      return;
    }

    this.loading = true;
    this.rackData = null;
    this.locations = [];
    this.currentPage = 1;

    this.service.get('store/wms.php?type=trackRack&barcode=' + encodeURIComponent(barcode) + '&page=' + this.currentPage + '&limit=' + this.pageSize).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.rackData = response['data']['rack'];
          this.locations = Array.isArray(response['data']['locations']) ? response['data']['locations'] : [];
          this.totalRecords = response['data']['total'] || 0;
          console.log('Rack Data:', this.rackData);
          console.log('Locations:', this.locations);
          console.log('Total Records:', this.totalRecords);
          this.barcodeInput = '';
          setTimeout(() => {
            if (this.barcodeInputField) {
              this.barcodeInputField.nativeElement.focus();
            }
          }, 100);
        } else {
          alertify.error(response['message'] || 'Rack not found');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error tracking rack. Please try again.');
        console.error(error);
      }
    );
  }

  loadPage(page: number) {
    if (!this.rackData) return;
    this.currentPage = page;
    this.service.get('store/wms.php?type=trackRack&barcode=' + encodeURIComponent(this.rackData.rack_no) + '&page=' + this.currentPage + '&limit=' + this.pageSize).subscribe(
      (response) => {
        if (response && response['status'] === 'success') {
          this.locations = response['data']['locations'] || [];
          this.totalRecords = response['data']['total'] || 0;
        }
      }
    );
  }

  clearSearch() {
    this.barcodeInput = '';
    this.rackData = null;
    this.locations = [];
    setTimeout(() => {
      if (this.barcodeInputField) {
        this.barcodeInputField.nativeElement.focus();
      }
    }, 100);
  }

}
