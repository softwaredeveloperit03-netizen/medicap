import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-empty-palate-location',
  templateUrl: './empty-palate-location.component.html',
  styleUrls: ['./empty-palate-location.component.css']
})
export class EmptyPalateLocationComponent implements OnInit {

  barcodeInput: string = '';
  loading = false;
  mappingData: any = null;
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
        this.findMapping(barcode);
      }
    }
  }

  findMapping(barcode: string) {
    if (!barcode || barcode.trim() === '') {
      alertify.error('Please scan or enter palate barcode');
      return;
    }

    this.loading = true;
    this.mappingData = null;
    this.materials = [];

    this.service.get('store/wms.php?type=getPalateLocationMapping&barcode=' + encodeURIComponent(barcode) + '&page=' + this.currentPage + '&limit=' + this.pageSize).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.mappingData = response['data']['mapping'];
          this.materials = Array.isArray(response['data']['materials']) ? response['data']['materials'] : [];
          this.totalRecords = response['data']['total'] || 0;
          console.log('Mapping Data:', this.mappingData);
          console.log('Materials:', this.materials);
          console.log('Total Records:', this.totalRecords);
        } else {
          alertify.error(response['message'] || 'Palate not found or not mapped to any location');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error finding mapping. Please try again.');
        console.error(error);
      }
    );
  }

  confirmEmpty() {
    if (!this.mappingData) return;

    const palateNo = this.mappingData?.mapping?.palate?.paletteNo || this.mappingData?.palate?.paletteNo;
    const locationNo = this.mappingData?.mapping?.location?.locationNo || this.mappingData?.location?.locationNo;
    
    if (!confirm('Are you sure you want to remove palate ' + palateNo + ' from location ' + locationNo + '?')) {
      return;
    }

    this.loading = true;
    this.service.post('store/wms.php?type=emptyPalateFromLocation', JSON.stringify({
      palate_barcode: palateNo,
      location_barcode: locationNo
    })).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          alertify.success('Palate removed from location successfully');
          this.resetForm();
        } else {
          alertify.error(response['message'] || 'Failed to remove palate');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error removing palate. Please try again.');
        console.error(error);
      }
    );
  }

  resetForm() {
    this.barcodeInput = '';
    this.mappingData = null;
    this.materials = [];
    setTimeout(() => {
      if (this.barcodeInputField) {
        this.barcodeInputField.nativeElement.focus();
      }
    }, 100);
  }

}
