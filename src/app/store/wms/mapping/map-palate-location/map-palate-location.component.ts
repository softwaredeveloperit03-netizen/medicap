import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-map-palate-location',
  templateUrl: './map-palate-location.component.html',
  styleUrls: ['./map-palate-location.component.css']
})
export class MapPalateLocationComponent implements OnInit {

  palateBarcode: string = '';
  locationBarcode: string = '';
  palateData: any = null;
  locationData: any = null;
  existingPalate: any = null;
  loading = false;

  @ViewChild('palateInput', { static: false }) palateInput: ElementRef;
  @ViewChild('locationInput', { static: false }) locationInput: ElementRef;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    setTimeout(() => {
      if (this.palateInput) {
        this.palateInput.nativeElement.focus();
      }
    }, 100);
  }

  onPalateScan(event: KeyboardEvent) {
    if (event.key === 'Enter') {
      const barcode = this.palateBarcode.trim();
      if (barcode) {
        this.validatePalate(barcode);
      }
    }
  }

  onLocationScan(event: KeyboardEvent) {
    if (event.key === 'Enter') {
      const barcode = this.locationBarcode.trim();
      if (barcode) {
        this.validateLocation(barcode);
      }
    }
  }

  validatePalate(barcode: string) {
    this.loading = true;
    this.service.get('store/wms.php?type=validatePalate&barcode=' + encodeURIComponent(barcode)).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.palateData = response['data'];
          if (this.locationData) {
            this.checkLocationOccupancy();
          } else {
            this.locationInput.nativeElement.focus();
          }
        } else {
          alertify.error(response['message'] || 'Palate not found');
          this.palateData = null;
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error validating palate. Please try again.');
        this.palateData = null;
      }
    );
  }

  validateLocation(barcode: string) {
    this.loading = true;
    this.service.get('store/wms.php?type=validateLocation&barcode=' + encodeURIComponent(barcode)).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.locationData = response['data'];
          if (this.palateData) {
            this.checkLocationOccupancy();
          }
        } else {
          alertify.error(response['message'] || 'Location not found');
          this.locationData = null;
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error validating location. Please try again.');
        this.locationData = null;
      }
    );
  }

  checkLocationOccupancy() {
    if (!this.locationData) return;
    this.loading = true;
    this.service.get('store/wms.php?type=checkLocationOccupancy&location_barcode=' + encodeURIComponent(this.locationData.locationNo)).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success' && response['data']['occupied']) {
          this.existingPalate = response['data']['palate'];
        } else {
          this.existingPalate = null;
        }
      },
      (error) => {
        this.loading = false;
        console.error(error);
      }
    );
  }

  saveMapping() {
    if (!this.palateData || !this.locationData) {
      alertify.error('Please scan both palate and location barcodes');
      return;
    }

    if (this.existingPalate && this.existingPalate.paletteNo !== this.palateData.paletteNo) {
      if (!confirm('Location is already occupied by palate ' + this.existingPalate.paletteNo + '. Do you want to replace it?')) {
        return;
      }
    }

    const data = {
      palate_barcode: this.palateData.paletteNo,
      location_barcode: this.locationData.locationNo
    };

    this.loading = true;
    this.service.post('store/wms.php?type=mapPalateToLocation', JSON.stringify(data)).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          alertify.success('Mapping saved successfully');
          this.resetForm();
        } else {
          alertify.error(response['message'] || 'Failed to save mapping');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error saving mapping. Please try again.');
        console.error(error);
      }
    );
  }

  resetForm() {
    this.palateBarcode = '';
    this.locationBarcode = '';
    this.palateData = null;
    this.locationData = null;
    this.existingPalate = null;
    setTimeout(() => {
      if (this.palateInput) {
        this.palateInput.nativeElement.focus();
      }
    }, 100);
  }

}
