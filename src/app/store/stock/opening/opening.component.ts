import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-opening',
  templateUrl: './opening.component.html',
  styleUrls: ['./opening.component.css'],
})
export class OpeningComponent implements OnInit {
  isNew = false;
  loading = false;
  clicked = false;
  searchQuery = '';
  materials: any[] = [];
  productList: any[] = [];
  clients: any[] = [];
  clientsSubGrps: any[] = [];
  vendors: any[] = [];
  material_type = 'Raw Material';

  constructor(private service: DataAccessService, private router: Router) {}

  ngOnInit() {
    this.getActiveClient();
    this.getSubMaterials(this.material_type);
  }

  get filteredMaterials(): any[] {
    if (!Array.isArray(this.materials)) {
      return [];
    }
    const q = (this.searchQuery || '').trim().toLowerCase();
    if (!q) {
      return this.materials;
    }
    return this.materials.filter((m) => {
      const name = String(m?.material_name || '').toLowerCase();
      const code = String(m?.material_code || '').toLowerCase();
      return name.includes(q) || code.includes(q);
    });
  }

  addProduct() {
    const selectedItems = (this.materials || []).filter((item) => item.selected);

    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }

    const invalids = selectedItems.filter(
      (m) =>
        m?.total_containers == '' ||
        m?.batch_no == '' ||
        m?.qty <= 0 ||
        m?.grn_no == '' ||
        m?.mfg_date == '' ||
        m?.exp_date == '' ||
        m?.grn_date == '' ||
        m?.qty == null ||
        m?.clientGrpCode == null ||
        m?.clientGrpCode === ''
    );

    if (invalids.length) {
      alert('Some selected items are missing required fields!!!!');
      return;
    }

    selectedItems.forEach((item) => {
      const isDuplicate = this.productList.some(
        (product) => product.material_code === item.material_code && product.batch_no === item.batch_no
      );

      if (!isDuplicate) {
        this.productList.push({
          material_name: item.material_name,
          material_code: item.material_code,
          batch_no: item.batch_no,
          pack_size: item.pack_size,
          qty: item.qty,
          unit: item.unit,
          ar_no: item.ar_no || item.batch_no,
          grn_no: item.grn_no,
          grn_date: item.grn_date,
          assay: item.assay,
          mfg_date: item.mfg_date,
          exp_date: item.exp_date,
          release_date: item.release_date,
          vendor_no: item.vendor_no,
          clientGrpCode: item.clientGrpCode,
          clientSubGrpCode: item.clientSubGrpCode,
          total_containers: item.total_containers,
        });
      }
    });

    this.materials.forEach((item) => (item.selected = false));
  }

  getActiveClient() {
    this.service.get('marketing/client.php?type=getActiveClient').subscribe((response) => {
      this.clients = Array.isArray(response) ? response : [];
    });
  }

  getClientSeries(client_code) {
    this.service.get('marketing/client.php?type=getClientSeries&client_code=' + client_code).subscribe((response) => {
      this.clientsSubGrps = Array.isArray(response) ? response : [];
    });
  }

  getSubMaterials(value) {
    const materialType = String(value || '').trim() || 'Raw Material';
    this.material_type = materialType;
    this.loading = true;
    this.materials = [];
    this.service.get('common.php?type=getMaterialsforstock&material_type=' + encodeURIComponent(materialType)).subscribe(
      (response) => {
        this.materials = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      () => {
        this.materials = [];
        this.loading = false;
      }
    );
    this.getVendorsByMatType(materialType);
  }

  getVendorsByMatType(value) {
    this.service.get('common.php?type=getVendorsByMatType&material_type=' + encodeURIComponent(value)).subscribe((response) => {
      this.vendors = Array.isArray(response) ? response : [];
    });
  }

  saveOpeningStock(_data) {
    if (this.productList.length == 0) {
      alert('Please Select At Least One Material To Proceed...');
      return;
    }
    this.clicked = true;
    const temp = { material_list: this.productList };
    this.service.post('store/dispensing.php?type=saveStock', JSON.stringify(temp)).subscribe(
      (response) => {
        this.clicked = false;
        if (response['status'] == 'success') {
          alertify.success('Record Saved Successfully');
          this.router.navigate(['/store']);
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      },
      () => {
        this.clicked = false;
        alertify.error('Failed: An error occured, please try again!');
      }
    );
  }
}
