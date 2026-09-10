import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-camera',
  templateUrl: './camera.component.html',
  styleUrls: ['./camera.component.css']
})
export class CameraComponent implements OnInit {

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getApprovedPOForBarcodePrinting();
  }

  po_type = 'Raw Material';
  results;

  getApprovedPOForBarcodePrinting() {
    this.service.get('purchase/po/raw.php?type=getApprovedPOForBarcodePrinting&po_type=' + this.po_type).subscribe((response) => {
      this.results = response;
    });
  }

  isView = false;
  selectedPO: any = {};

  view(data) {
    this.selectedPO = data;
    this.isView = true;
  }

  resolveTrackingId(user: any): string {
    if (user?.trackingId) {
      return String(user.trackingId).trim();
    }
    const poNo = this.selectedPO?.po_no;
    const materialCode = user?.material_code;
    if (poNo && materialCode) {
      return `${poNo}-${materialCode}`;
    }
    return '';
  }

  printBarcode(user: any): void {
    if (!user?.material_code) {
      alertify.error('Material code is missing.');
      return;
    }

    const trackingId = this.resolveTrackingId(user);
    if (!trackingId) {
      alertify.error('Tracking ID is not available for this material.');
      return;
    }

    const copies = Math.max(1, Number(user.NoOfBarcode) || 1);

    this.service.open(
      'purchase/po/print_barcode.php?material_code=' + encodeURIComponent(user.material_code)
      + '&material_name=' + encodeURIComponent(user.material_name || user.material_code)
      + '&trackingId=' + encodeURIComponent(trackingId)
      + '&total_containers=' + encodeURIComponent(String(copies))
    );
  }

  searchQuery;

  get filteredMaterials(): any[] {
    if (!this.results) {
      return [];
    }
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.results;
    }

    const query = this.searchQuery.toLowerCase().trim();

    return this.results.filter((material) => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return (
            dateValue instanceof Date &&
            dateValue.toISOString().slice(0, 10).includes(query)
          );
        }
        return value && value.toString().toLowerCase().includes(query);
      });
    });
  }
}
