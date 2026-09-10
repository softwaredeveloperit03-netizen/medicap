import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx';
import * as FileSaver from 'file-saver';

declare let alertify;

@Component({
  selector: 'app-prodlog',
  templateUrl: './prodlog.component.html',
  styleUrls: ['./prodlog.component.css']
})
export class ProdlogComponent implements OnInit {
  results;


  constructor(private service: DataAccessService) {
    this.loggedInDept = localStorage.getItem('department');
    this.plant_id = this.service.getPlantConfigFields('plant_id');
    this.plant_type = this.service.getPlantConfigFields('plant_type');
  }
  plant_id;
  plant_type;
  ngOnInit(): void {
    this.getMaterialsLog();



    this.get_rights();
  }
  // -----------------------------------------12th july------------------------------------------//

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
  //---------------------------------------------------------------------------------//
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10;
  }

  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }

  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }

  materials = [];
  material_subtype;
  material_nature;
  grade
  getMaterialsLog() {
    this.service
      .get(
        'master/product.php?type=getProductsLog'
      )
      .subscribe((response) => {
        this.results = response;

      });
  }
  material_name;
  material_code ;

  alternate_uom;
  isEdit = false;
  id;
  editRawMaterial(data) {
    let temp = data.value;
    temp['id'] = this.id;

    this.service
      .post(
        'master/material.php?type=updateProductStatus',
        JSON.stringify(temp)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success('Material Updates Successfully');
          this.isEdit = false;
          this.getMaterialsLog();
        } else {
          alertify.error(response['status']);
        }
      });
  }
  selectedResult=[];
  lead_time;
purchase_lead_time;
moisture;
PurchaseDeliveryTime;
ForPayment;
work_orderDate;
batchApprovalDate;
LineClearanceDate;
DispensingDate;
productionDate;
PackingDate;
FgTransferDate;
DispatchDate;
  edit(index) {

      this.selectedResult = this.results[index];
      this.lead_time=this.selectedResult['lead_time']
      this.purchase_lead_time=this.selectedResult['purchase_lead_time']
      this.moisture=this.selectedResult['moisture']
      this.ForPayment=this.selectedResult['ForPayment']
      this.PurchaseDeliveryTime=this.selectedResult['PurchaseDeliveryTime']
      this.work_orderDate=this.selectedResult['work_orderDate']
      this.batchApprovalDate=this.selectedResult['batchApprovalDate']
      this.LineClearanceDate=this.selectedResult['LineClearanceDate']
      this.DispensingDate=this.selectedResult['DispensingDate']
      this.productionDate=this.selectedResult['productionDate']
      this.PackingDate=this.selectedResult['PackingDate']
      this.FgTransferDate=this.selectedResult['FgTransferDate']
      this.DispatchDate=this.selectedResult['DispatchDate']
      

    this.id = this.selectedResult['id'];
    this.isEdit = true;

  }
  searchQuery;

}
