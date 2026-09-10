import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-map-material-palate',
  templateUrl: './map-material-palate.component.html',
  styleUrls: ['./map-material-palate.component.css']
})
export class MapMaterialPalateComponent implements OnInit {

  materialBarcode: string = '';
  palateBarcode: string = '';
  materialData: any = null;
  palateData: any = null;
  loading = false;
  isBatchMode = false;
  mappedMaterials: any[] = [];

  @ViewChild('materialInput', { static: false }) materialInput: ElementRef;
  @ViewChild('palateInput', { static: false }) palateInput: ElementRef;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    setTimeout(() => {
      if (this.materialInput) {
        this.materialInput.nativeElement.focus();
      }
    }, 100);
  }

  onMaterialScan(event: KeyboardEvent) {
    if (event.key === 'Enter') {
      const barcode = this.materialBarcode.trim();
      if (barcode) {
        this.validateMaterial(barcode);
      }
    }
  }

  onPalateScan(event: KeyboardEvent) {
    if (event.key === 'Enter') {
      const barcode = this.palateBarcode.trim();
      if (barcode) {
        this.validatePalate(barcode);
      }
    }
  }

  validateMaterial(barcode: string) {
    this.loading = true;
    this.service.get('store/wms.php?type=validateMaterial&barcode=' + encodeURIComponent(barcode)).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.materialData = response['data'];
          if (this.palateData) {
            this.palateInput.nativeElement.focus();
          } else {
            this.palateInput.nativeElement.focus();
          }
        } else {
          alertify.error(response['message'] || 'Material not found');
          this.materialData = null;
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error validating material. Please try again.');
        this.materialData = null;
      }
    );
  }

  validatePalate(barcode: string) {
    this.loading = true;
    this.service.get('store/wms.php?type=validatePalate&barcode=' + encodeURIComponent(barcode)).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.palateData = response['data'];
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

  addToBatch() {
    if (!this.isBatchMode) {
      alertify.error('Please enable Batch Mode first');
      return;
    }
    
    if (!this.palateData) {
      alertify.error('Please scan palate barcode first');
      return;
    }
    
    if (!this.materialData) {
      alertify.error('Please scan material barcode first');
      return;
    }
    
    const exists = this.mappedMaterials.find(m => m.tracking_id === this.materialData.tracking_id);
    if (!exists) {
      this.mappedMaterials.push({
        tracking_id: this.materialData.tracking_id,
        material_code: this.materialData.material_code,
        material_name: this.materialData.material_name,
        batch_no: this.materialData.batch_no
      });
      alertify.success('Material added to batch');
      this.materialData = null;
      this.materialBarcode = '';
      setTimeout(() => {
        if (this.materialInput) {
          this.materialInput.nativeElement.focus();
        }
      }, 100);
    } else {
      alertify.warning('Material already added to batch');
      this.materialData = null;
      this.materialBarcode = '';
      setTimeout(() => {
        if (this.materialInput) {
          this.materialInput.nativeElement.focus();
        }
      }, 100);
    }
  }

  removeFromBatch(index: number) {
    this.mappedMaterials.splice(index, 1);
  }

  saveMapping() {
    if (!this.palateData) {
      alertify.error('Please scan palate barcode first');
      return;
    }

    if (this.isBatchMode && this.mappedMaterials.length === 0) {
      alertify.error('Please add materials to batch');
      return;
    }

    if (!this.isBatchMode && !this.materialData) {
      alertify.error('Please scan material barcode first');
      return;
    }

    const materials = this.isBatchMode 
      ? this.mappedMaterials.map(m => m.tracking_id)
      : [this.materialData.tracking_id];

    const data = {
      palate_barcode: this.palateData.paletteNo,
      materials: materials
    };

    this.loading = true;
    this.service.post('store/wms.php?type=mapMaterialToPalate', JSON.stringify(data)).subscribe(
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
    this.materialBarcode = '';
    this.palateBarcode = '';
    this.materialData = null;
    this.palateData = null;
    this.mappedMaterials = [];
    this.isBatchMode = false;
    setTimeout(() => {
      if (this.materialInput) {
        this.materialInput.nativeElement.focus();
      }
    }, 100);
  }

  toggleBatchMode() {
    // This is called when checkbox changes, but ngModel already updated isBatchMode
    // So we don't need to toggle again, just handle the side effects
    if (!this.isBatchMode) {
      this.mappedMaterials = [];
      this.materialData = null;
      this.materialBarcode = '';
    }
    setTimeout(() => {
      if (this.materialInput) {
        this.materialInput.nativeElement.focus();
      }
    }, 100);
  }

}
