import { Time } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { ClrLoadingState } from '@clr/angular';
import { Observable, of, throwError } from 'rxjs';
import { DataAccessService } from 'src/app/data-access.service';
import * as XLSX from 'xlsx/xlsx.mjs';
declare let alertify;

@Component({
  selector: 'app-bulk-upload',
  templateUrl: './bulk-upload.component.html',
  styleUrls: ['./bulk-upload.component.css']
})
export class BulkUploadComponent implements OnInit {
  offline_attendance:any;
  is_edit:boolean=false;
  tableData: Object;
  showTable:boolean=false;
  tableValue:any;
  showLoader:boolean=false;
  emp_no:any;
  EditBulkData: any=[];
  tableValue1: any;
  updateId: any;
  date: Date;
  in_time: Time;
  out_time: Time;
  empdata: Object;
  i: any;
  status: any;
  empRes: any;
  plant_id;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.plant_id = this.service.getPlantConfigFields("plant_id")

  }
//   onFileChange(ev) {

//     let workBook = null;
//     let jsonData = null;
//     const reader = new FileReader();
//     const selectedFile = ev.target.files[0];
//     reader.onload = (event) => {
//       const data = reader.result;
//       workBook = XLSX.read(data, { type: 'binary' });
//       jsonData = workBook.SheetNames.reduce((initial, name) => {
//         const sheet = workBook.Sheets[name];
//         initial[name] = XLSX.utils.sheet_to_json(sheet);
//         return initial;
//       }, {});
//       const dataString = JSON.stringify(jsonData);
//       let dataObj = JSON.parse(dataString)
//        this.offline_attendance = dataObj['Sheet1'];

//     }
//     reader.readAsBinaryString(selectedFile);

//  }

 saveuploadData(event){

  this.showLoader=true;
  const formData = new FormData();
  formData.append('attendance_file', event.target.files[0]);
  console.log(formData);

  this.service.post('hr/attendance.php?type=saveMultipleAttendance&plant_id1='+this.plant_id,formData).subscribe(response => {
    this.showTable=true;
    this.showLoader=false;
    console.log(response);
    this.offline_attendance = response;
    console.log(this.offline_attendance.data)
    this.tableValue= this.offline_attendance.data;
    this.tableValue1= this.offline_attendance.data1;

  });
}
 saveuploadData1(event){

  this.showLoader=true;
  const formData = new FormData();
  formData.append('attendance_file', event.target.files[0]);
  console.log(formData);

  this.service.post('hr/attendance.php?type=saveMultipleAttendance_amardeep&plant_id1='+this.plant_id,formData).subscribe(response => {
    this.showTable=true;
    this.showLoader=false;
    console.log(response);
    this.offline_attendance = response;
    console.log(this.offline_attendance.data)
    this.tableValue= this.offline_attendance.data;
    this.tableValue1= this.offline_attendance.data1;

  });
}

saveAttendance(data)
{

  // this.EditBulkData = data ;
  // console.log(this.EditBulkData.value)
  let empdata = { 
    bulkData: this.EditBulkData.value,
    updateId: this.updateId
  }

  this.service.post('hrDepartment.php?type=updateEmp',JSON.stringify(empdata)).subscribe(response => {
  console.log(response);
  if (response['Status'] === 'success') {
    alertify.success("Data saved Successfully");
    let i = this.i;
    this.empdata = response['empdata'];
    this.tableValue[i]={
      id:this.empdata['id'],
      emp_id:this.empdata['emp_id'],
      department:this.empdata['department'],
      firstname:this.empdata['firstname'],
      indate:this.empdata['indate'],
      intime:this.empdata['intime'],
      outtime:this.empdata['outtime'],
    },
    this.is_edit=false;

    console.log(this.tableValue)
  }
  // else if(response['Status']=='failed')
  // {
  //   this.status = 'Failed';
  // }
  else {
    alertify.error("Failed, An error occured, please try again!");

  }
  });
}

EditData(index,rowId,data){
console.log(index);
this.i = index;
this.is_edit=true;
this.updateId = rowId
console.log(data);
this.emp_no = data.emp_id;
this.date = data.indate;
this.in_time = data.intime;
this.out_time = data.outtime;
}

deleteData(index,rowId){
  this.service.post('hrDepartment.php?type=deleteATN',rowId).subscribe(response => {
    console.log(response);
    if (response['Status'] === 'success') {
      alertify.success("Data Deleted Successfully");
      this.tableValue.splice(index, 1 );
    }
    else {
      alertify.error("Failed, An error occured, please try again!");

    }
  });
}

download(){
  // this.service.open('hr/attendance/bulk-upload.php?type=downloadbulk-upload')
  window.open('https://gmpsoftwareindia.com/php/gmptotal/hr/attandance.xlsx')

}

onBlurMethod()
{
  console.log(this.emp_no)
  let temp = this.emp_no;
  this.service.post('hrDepartment.php?type=getEmp',JSON.stringify({data: temp})).subscribe(response => {
    console.log(response);
    if (response['Status'] === 'success') { 
      this.status = 'Success';
      this.empdata = response['data'];
    }
    else
    {
      this.status = 'Failed';
    }
  });
}

}
