import { DatePipe } from '@angular/common';
import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css'],
  providers: [DatePipe]
})
export class AllocationComponent implements OnInit {
  department;
  result;
  shift;
  list=[];
  shifts;
  

  max_date = '';
  from_date = '';
  to_date = '';
    departments;
    selectedDepartmentData;
  constructor(private service:DataAccessService, private router: Router, private datePipe: DatePipe) {
    let date = new Date();
    this.max_date = this.datePipe.transform(date, 'dd-MM-yyyy');
    // this.from_date = this.max_date;
  }

  ngOnInit() {
   
    this.getShiftList()
    this.getDepartments();
    this.selectedDepartmentData = this.service.getPlantConfigFields("selectedDepartmentData")
  }
  getEmployees(value){

    

    this.service.get('hr/shift.php?type=getEmployees&department1='+value).subscribe(response=>{
      this.result=response;
      for(let i = 0; i < this.result.length; i++){

       
      this.result[i]['start_date'] = this.result[i]['Start_date'] ? this.formatToDisplayDate(this.result[i]['Start_date']) : '';
      this.result[i]['end_date'] = this.result[i]['End_date'] ? this.formatToDisplayDate(this.result[i]['End_date']) : '';
      // Initialize `from_date` and `to_date` in `dd-MM-yyyy` format
      this.result[i]['from_date'] = '';
      this.result[i]['to_date'] = '';
      return this.result;
    }
    })
 
  }
  calculateMaxDate(endDate: string): string {
    if (!endDate) return '';  
    const nextDay = new Date(endDate);
    nextDay.setDate(nextDay.getDate() + 1);  
    return nextDay.toISOString().split('T')[0]; 
  }



  formatToDisplayDate(date: string): string {
    if (!date) return '';
    const [year, month, day] = date.split('-');
    return `${day}-${month}-${year}`;
  }



  formatToISODate(date: string): string {
    if (!date) return '';
    const [day, month, year] = date.split('-');
    return `${year}-${month}-${day}`;
  }
  getDepartments() {
    this.service.get('hr/employee.php?type=get_department_by_designation')
      .subscribe(response => {
        this.departments = response;
      });
  }
 
  getShiftList(){
    this.service.get('hr/shift.php?type=getShiftList').subscribe(response=>{
      this.shifts=response;
    })
  }

  save(data) {
    if (this.resultList.length==0) {
      alertify.error('Please select Shift');
      return;
    }

    this.service.post('hr/shift.php?type=save_shiftAllocate&from_date=' + this.from_date + '&to_date=' + this.to_date,JSON.stringify(this.resultList)).subscribe(response=>{
      if (response['status'] == 'success') {
        alertify.success('Records saved successfully');
        this.router.navigate(['/hr/shift']);
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }





  resultList=[]
  // addProduct(data) {
  //   const selectedItems = this.result.filter((term) => term.selected);
  
  //   if (selectedItems.length === 0) {
  //     alert('No items selected');
  //     return;
  //   }
     
  //     this.resultList.push(
  //       ...selectedItems.map((item) => ({
  //         emp_id: item.emp_id,
  //         emp_name: item.emp_name,
  //         designation: item.designation,
  //         department: item.department,
  //         shift: item.shift,
  //         weekly_off: item.weekly_off ,
  //         from_date: item.from_date , 
  //         to_date: item.to_date ,
  //       }))
  //     );
 
 
  
  //   // Reset selected property for each selected item
  //   for (const item of selectedItems) {
  //     item.selected = false;
  //   }
  
  //   data.resetForm();
  // console.log(this.resultList)





  // this.resultList.forEach(result => {
  //   const shift = this.shifts.find(s => s.shift === result.id.toString());
  //   if (shift) {
  //     result.shift_name = shift.shift_name;
  //   }
  // });

  // console.log(this.resultList);  


  // }
  addProduct(data) {
    const selectedItems = this.result.filter((term) => term.selected);
  
    if (selectedItems.length === 0) {
      alert('No items selected');
      return;
    }
    
    this.resultList.push(
      ...selectedItems.map((item) => ({
        emp_id: item.emp_id,
        emp_name: item.emp_name,
        designation: item.designation,
        department: item.department,
        shift: item.shift,
        weekly_off: item.weekly_off,
        from_date: item.from_date,
        to_date: item.to_date,
      }))
    );
    this.result = this.result.filter(term => !term.selected);

  
     for (const item of selectedItems) {
      item.selected = false;
    }
  
    data.resetForm();
    console.log(this.resultList);
  
     this.resultList.forEach(result => {
      const shift = this.shifts.find(s => s.id === result.shift.toString());
      if (shift) {
        result.shift_name = shift.shift_name;
      }
    });
  


    console.log(this.resultList);
  } 
  
   
  deleteProducts(index) {

 
this.result.push(this.resultList[index]);
  
    this.resultList.splice(index, 1);
  }






















  weekly_off = '';
 

  addWeeklyOff(){
    for(let i = 0; i<this.result.length; i++){
      this.result[i].weekly_off = this.weekly_off;
    }
  }
  addShift(){
    for(let i = 0; i<this.result.length; i++){
      this.result[i].shift = this.shift;
    }
  }
  addfrom_date(){
    for(let i = 0; i<this.result.length; i++){
      this.result[i].from_date = this.from_date;
    }
  }
  addto_date(){
    for(let i = 0; i<this.result.length; i++){
      this.result[i].to_date = this.to_date;
    }
  }

 





}
