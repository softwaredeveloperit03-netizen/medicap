import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-form',
  templateUrl: './form.component.html',
  styleUrls: ['./form.component.css']
})
export class FormComponent implements OnInit {

  bulkname = '';
  bulkcode = '';
  arno = '';
  mfg_date = '';
  exp_date = '';
  mfg_qty = '';
  bulkMasterList: any = [];
  selectedBulkMaster: any = {};

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit(): void {
    this.getBulkMasterList();
  }

  getBulkMasterList() {
    this.service.get('production/master.php?type=getApprovedBulk').subscribe(response => {
      this.bulkMasterList = response || [];
    }, error => {
      console.error('Error fetching bulk master list:', error);
    });
  }

  onBulkNameChange() {
    const selectedBulk = this.bulkMasterList.find(bulk => 
      (bulk.bulkName || bulk.bulk_name || bulk.bulkname) === this.bulkname
    );
    
    if (selectedBulk) {
      this.selectedBulkMaster = selectedBulk;
      this.bulkcode = selectedBulk.bulkCode || selectedBulk.bulk_code || selectedBulk.bulkcode || '';
    } else {
      this.selectedBulkMaster = {};
      this.bulkcode = '';
    }
  }

  saveForm() {
    if (!this.bulkname || !this.bulkcode || !this.arno || !this.mfg_date || !this.exp_date || !this.mfg_qty) {
      alert('Please fill all required fields');
      return;
    }

    const formData = {
      bulkname: this.bulkname,
      bulkcode: this.bulkcode,
      arno: this.arno,
      mfg_date: this.mfg_date,
      exp_date: this.exp_date,
      mfg_qty: this.mfg_qty
    };

    this.service.post('production/bulk_stock.php?type=saveBulkStock', JSON.stringify(formData)).subscribe(response => {
      const result = JSON.parse(JSON.stringify(response));
      if (result.status === 'success') {
        alert('Bulk Stock Saved Successfully');
        this.resetForm();
        this.router.navigate(['/fproduction/bulk_stock']);
      } else {
        alert('An error has occurred, please try again');
      }
    }, error => {
      alert('An error has occurred, please try again');
    });
  }

  resetForm() {
    this.bulkname = '';
    this.bulkcode = '';
    this.arno = '';
    this.mfg_date = '';
    this.exp_date = '';
    this.mfg_qty = '';
    this.selectedBulkMaster = {};
  }

  close() {
    this.router.navigate(['/fproduction/bulk_stock']);
  }

}

