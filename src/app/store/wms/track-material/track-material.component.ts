import { Component, OnInit, ViewChild, ElementRef } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-track-material',
  templateUrl: './track-material.component.html',
  styleUrls: ['./track-material.component.css']
})
export class TrackMaterialComponent implements OnInit {

  barcodeInput: string = '';
  loading = false;
  materialData: any = null;
  searchQuery: string = '';

  @ViewChild('barcodeInputField', { static: false }) barcodeInputField: ElementRef;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    // Auto-focus barcode input after view init
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
        this.trackMaterial(barcode);
      }
    }
  }

  trackMaterial(barcode: string) {
    if (!barcode || barcode.trim() === '') {
      alertify.error('Please scan or enter material barcode');
      return;
    }

    this.loading = true;
    this.materialData = null;

    this.service.get('store/wms.php?type=trackMaterial&barcode=' + encodeURIComponent(barcode)).subscribe(
      (response) => {
        this.loading = false;
        if (response && response['status'] === 'success') {
          this.materialData = response['data'];
          this.barcodeInput = ''; // Clear for next scan
          // Auto-focus for next scan
          setTimeout(() => {
            if (this.barcodeInputField) {
              this.barcodeInputField.nativeElement.focus();
            }
          }, 100);
        } else {
          alertify.error(response['message'] || 'Material not found or not mapped');
        }
      },
      (error) => {
        this.loading = false;
        alertify.error('Error tracking material. Please try again.');
        console.error(error);
      }
    );
  }

  clearSearch() {
    this.barcodeInput = '';
    this.materialData = null;
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
