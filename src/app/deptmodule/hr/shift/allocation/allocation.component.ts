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
    this.max_date = this.datePipe.transform(date, 'yyyy-MM-dd');
    // this.from_date = this.max_date;
  }

  ngOnInit() {
   
    this.getShiftList()
    this.getEmployees();
    this.selectedDepartmentData = this.service.getPlantConfigFields("selectedDepartmentData")
  }



  getEmployees(){
    this.service.get('hr/shift.php?type=getEmployees&department1='+localStorage.getItem('department')).subscribe(response=>{
      this.result=response;
    })
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
  
    // Reset selected property for each selected item
    for (const item of selectedItems) {
      item.selected = false;
    }
  
    data.resetForm();
    console.log(this.resultList);
  
    // Merge shift_name from shifts into resultList
    this.resultList.forEach(result => {
      const shift = this.shifts.find(s => s.id === result.shift.toString());
      if (shift) {
        result.shift_name = shift.shift_name;
      }
    });
  
    console.log(this.resultList);
  } 
  
   
  deleteProducts(index) {
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
