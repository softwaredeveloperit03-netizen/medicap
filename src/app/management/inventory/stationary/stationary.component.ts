import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-stationary',
  templateUrl: './stationary.component.html',
  styleUrls: ['./stationary.component.css'],
})
export class StationaryComponent implements OnInit {
  stocks;
  vendors;
  loading;
  results;

  selectedMaterial = [];
  pdfLink = '';
  grn_no = '';
  materiallist = [];

  vendor_no = '';
  material_type = '';
  grade = '';
  status = '';
  material_name = '';
  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
  }

  ngOnInit() {
    this.getVendors();
    this.getAllStock(); /* 
    this.getMaterials(); */
    this.get_rights();
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe((response) => {
      this.vendors = response;
    });
  }
  /* 
  getMaterials(){
    this.service.get('store.php?type=rawmateriallist').subscribe((response:any) => {
      this.materiallist = response;
    });
  } */

  getAllStock() {
    // this.service.get('engineeringStore.php?type=getStock&status='+this.status+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name).subscribe(response => {
    this.service
      .get('engineeringStore.php?type=getStock')
      .subscribe((response) => {
        this.stocks = response;
        this.results = response;
      });
  }

  generatePDF() {
    let url =
      this.service.url +
      'pdf/stockbook.php?token=' +
      localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download() {
    this.service.open(
      'engineeringStore.php?type=rawStockLog&material_code=&from_date=&to_date='
    );
  }

  filterStock() {
    this.stocks = [];
    // console.log('test1', this.results);
    for (let i = 0; i < this.results.length; i++) {
      let data = this.results[i];
      // console.log('test2',data);
      if (
        data.material_type
          .toUpperCase()
          .includes(this.material_type.toUpperCase())
      ) {
        this.stocks.push(data);
      }
    }
  }

  clear() {
    this.material_type = '';
    this.stocks = this.results;
  }
}
