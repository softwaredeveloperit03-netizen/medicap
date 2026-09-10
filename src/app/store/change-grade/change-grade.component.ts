import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-change-grade',
  templateUrl: './change-grade.component.html',
  styleUrls: ['./change-grade.component.css'],
  providers:[DatePipe]
})
export class ChangeGradeComponent implements OnInit {

  stocks;
  vendors;
  selectedReport=[];
  selectedMaterial = [];
  pdfLink = '';
  grndetails=[];
  grn_no = '';
  materiallist = [];
  material_for='';
  vendor_no = '';
  material_type = '';
  grade = '';
  status = '';
  material_name = '';
  isView=false;
  
  grades = [
    {grade: 'IP'},
    {grade: 'Bp'},
    {grade: 'USP'},
    {grade: 'PH.Eur'},
    {grade: 'CP'},
    {grade: 'JP'},
    {grade: 'IHS'},
  ];
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getVendors();
    this.getAllStock();
    
    
  }

  getVendors() {
    this.service.get('common.php?type=getVendors').subscribe(response => {
      this.vendors = response;
    });
  }


 

  getAllStock() {
    this.service.get('store/raw.php?type=getApprovedStock&status='+this.status+'&material_for='+this.material_for+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name).subscribe(response => {
      this.stocks = response;
    });
  }

  generatePDF() {
    let url = this.service.url + 'pdf/stockbook.php?token=' + localStorage.getItem('token');
    window.open(url, '_blank');
  }

  download(){
    this.service.open('store/raw.php?type=downloadStock&status='+this.status+'&material_for='+this.material_for+'&vendor_no='+this.vendor_no+'&material_type='+this.material_type + '&grade=' + this.grade + '&material_name=' + this.material_name);
  }


  view(index) {
    this.selectedReport = this.stocks[index];
    this.grndetails=this.selectedReport['grn_details'];
    this.isView = true;
  }

  save(data) {
    if (!data.valid) {
      alertify.error('');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedReport['id'];
    temp['short_qty'] = this.selectedReport['short_qty'];
    temp['vendor_no']=this.selectedReport['vendor_no'];
    temp['material_code']=this.selectedReport['material_code'];
    temp['unit']=this.selectedReport['unit'];
    temp['accept_qty'] = this.selectedReport['accept_qty'];
    temp['reject_qty'] = this.selectedReport['reject_qty'];
    temp['batches']=this.selectedReport['batches'];
    temp['inword_no'] = this.selectedReport['inword_no'];
    this.service.post('store/raw.php?type=changeGrade', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('GRN Prepared successfully');
        this.isView = false;
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }



}
