import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';
import { NgForm } from '@angular/forms';

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css'],
})
export class NewComponent implements OnInit {
  selectedMaterial: any;

  constructor(private service: DataAccessService, private router: Router) {}

  capaRequired: string = '';
  ngOnInit(): void {
    this.getMaterials();
    this.dept=localStorage.getItem('department') || '';
  }
  dept: string = '';
  materialType: string = '';
  mat: any = [];
  selectedBatch: any = null;

  allStock: any[] = [];     // full API response
batchList: any[] = [];    // batches for selected material


batchNo = '';
mfgDate = '';
expiryDate = '';


  getMaterials() {
  if (!this.materialType) {
    this.resetAll();
    return;
  }

  this.service
    .get(
      'store/opening.php?type=getStockBook&Material_type=' +
        encodeURIComponent(this.materialType)
    )
    .subscribe((res: any[]) => {
      this.allStock = res || [];

      // extract unique materials
      this.mat = this.getUniqueMaterials(this.allStock);

      this.resetMaterialAndBatch();
    });
}

getUniqueMaterials(data: any[]) {
  const map = new Map<string, any>();

  data.forEach(item => {
    if (!map.has(item.material_code)) {
      map.set(item.material_code, {
        material_code: item.material_code,
        material_name: item.material_name
      });
    }
  });

  return Array.from(map.values());
}


  getBatchDetails() {
  if (!this.selectedMaterial) {
    this.clearBatchDetails();
    return;
  }

  this.batchList = this.allStock.filter(
    item => item.material_code === this.selectedMaterial.material_code
  );

  this.clearBatchFields();
}





 onBatchChange() {
  if (!this.selectedBatch) {
    this.clearBatchFields();
    return;
  }

  this.batchNo = this.selectedBatch.batch_no;
  this.mfgDate = this.selectedBatch.mfg_date;
  this.expiryDate = this.selectedBatch.exp_date;
}


resetMaterialAndBatch() {
  this.selectedMaterial = null;
  this.clearBatchDetails();
}

clearBatchFields() {
  this.selectedBatch = null;
  this.batchNo = '';
  this.mfgDate = '';
  this.expiryDate = '';
}

clearBatchDetails() {
  this.batchList = [];
  this.clearBatchFields();
}

resetAll() {
  this.allStock = [];
  this.mat = [];
  this.resetMaterialAndBatch();
}


  /** 🔹 SUBMIT FORM */
  save(form: NgForm) {
    if (form.invalid) {
      alert('Please fill all required fields');
      return;
    }

    this.service
      .post('qa/rca.php?type=saveRca', form.value)
      .subscribe((res: any) => {
        if (res?.status === 'success') {
          alert('RCA saved successfully');
          form.resetForm();
          this.router.navigate(['/qa/rootcause']);
        } else {
          alert('Failed to save RCA');
        }
      });
  }
}
