import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-empty-material-palate',
  templateUrl: './empty-material-palate.component.html',
  styleUrls: ['./empty-material-palate.component.css']
})
export class EmptyMaterialPalateComponent implements OnInit {

  barcodeInput: string = '';
  loading = false;
  mappingData: any = null;

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
      alertify.error('Please scan or enter material barcode');
      return;
    }

    this.loading = true;
    this.mappingData = null;

    this.service.get('store/wms.php?type=getMaterialPalateMapping&barcode=' + encodeURIComponent(barcode)).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.mappingData = response['data'];
          console.log('Mapping Data:', this.mappingData);
        } else {
          alertify.error(response['message'] || 'Material not found or not mapped to any palate');
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

    if (!confirm('Are you sure you want to remove material ' + this.mappingData.material.tracking_id + ' from palate ' + this.mappingData.palate.paletteNo + '?')) {
      return;
    }

    this.loading = true;
    this.service.post('store/wms.php?type=emptyMaterialFromPalate', JSON.stringify({
      material_barcode: this.mappingData.material.tracking_id,
      palate_barcode: this.mappingData.palate.paletteNo
    })).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          alertify.success('Material removed from palate successfully');
          this.resetForm();
        } else {
          alertify.error(response['message'] || 'Failed to remove material');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error removing material. Please try again.');
        console.error(error);
      }
    );
  }

  resetForm() {
    this.barcodeInput = '';
    this.mappingData = null;
    setTimeout(() => {
      if (this.barcodeInputField) {
        this.barcodeInputField.nativeElement.focus();
      }
    }, 100);
  }

}
