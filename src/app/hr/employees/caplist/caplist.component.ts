import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
import * as XLSX from 'xlsx';

@Component({
  selector: 'app-caplist',
  templateUrl: './caplist.component.html',
  styleUrls: ['./caplist.component.css']
})
export class CaplistComponent implements OnInit {
  show_only_annexure = false;
  isAnnexure = false
  isupdateAnnexure = false
  isView = false;
  isPassword = false;
  employees;
  emp_id;
  departments;
  designations;
  emp_name = '';
  department_name = '';
  department = '';
  designation = '';
  status = 'active';
  family_members = [];
  academics = [];
  employeement = []
  length;
  amtList = [];
  qualiList = [];
  languages = [];
  selectedResult = [];
  documents = [];
  from_date: any;
  isedit: boolean;
  salary_annexure_response;
  salary_response = [];
  offerletter = [];
  candidates = [];
  salary_heads;
  earnings_list = [];
  detuctions_list = [];
  ctc_list = [];
  salary_types;
   
  total_earnings = 0;
  total_deductions = 0;
  total_ctc = 0;

  total_earn_month_amt = 0;
  total_earn_annum_amt = 0;
  total_ded_month_amt = 0;
  total_ded_annum_amt = 0;
  total_ctc_ded_month_amt = 0;
  total_ctc_ded_annum_amt = 0;
  qualifications;
  isMetro = 'Non Metro';
  isPF = 'Yes';
  isESIC = 'No';
  special_allowance = 0;
  gross_salary = 0;
  deduction = 0;
  contribution = 0;
  inhand = 0;
  ctc = 0;
  item = [];
  basic = 0;
  HRA = 0;
  Conveyance = 0;
  medical = 0;
  specialAllowance = 0;
  educationalAllowance = 0;
  candidate_name = '';
  fixed_earnings_total = 0;
  calculateble_gross = 0;
  PF_EMP = 0;
  c_PF = 0;
  PF = 0;
  ESIC = 0;
  c_ESIC = 0;
  bonus = 0;
  p_tax = 0;
  canteen = 0;
  other = 0;
  appointmentletter = [];
  employee = [];
  gratuity = 0;
  emp_type = '';
  take_home_salary = 0;
  contribution_annual = 0;
  ctc_annual;
  plant_id;
  searchQuery;
  
  constructor(private service: DataAccessService, private router: Router) {
    this.plant_id = this.service.getPlantConfigFields("plant_id")
    this.loggedInDept = localStorage.getItem('department');

  }
  plantID='';
  plants;
 
  ngOnInit() {

    this.plants = JSON.parse(localStorage.getItem('all_plants'));



    this.service.observableDepartment.subscribe(response => {
      this.plant_id = this.service.getPlantConfigFields("plant_id")
      this.departments = response;
    });
 
    this.getMahesh();
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

  get_rights() {this.service.get('hr/employee.php?type=getrights&emp_id=' 
    +localStorage.getItem('emp_id') +'&dep_name=' +this.loggedInDept     
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


  getData(){
   

    this.getSalaryTypes();
    this.getEmployees();
    
  }

 
  getEmployees() {
    new Promise((res,rej)=>{
     this.service.get('hr/employee.php?type=HOgetEmployeesList&plantID='+this.plantID).subscribe(response => {
      this.employees = response;
      res(response);
    });
    })
  }
  getSalaryTypes() {
    this.service.get('hr/employee.php?type=HogetSalaryTypes&plantID='+this.plantID).subscribe(response => {
      this.salary_types = response;
      console.log('crazy',this.salary_types)
    });
  }


  designation_types;
  getMahesh() {
    this.service.get('hr/employee.php?type=getDesignationTypes').subscribe(response => {
      this.designation_types = response;
      console.log('rd',this.designation_types)
    });
  }
  parse(obj) {
    if (obj == null || obj.length == 0 || obj == '[]') {
      return [];
    } else {
      return JSON.parse(obj);
    }
  }
  showAnnexure(i) {
    const index = this.filteredMaterials.findIndex((employee, idx) => idx === i);
    if (index !== -1) {
        this.selectedResult = this.filteredMaterials[index];
        // Do something with this.selectedResult
    } else {
        console.error('Index not found');
    }
    this.getSalaryAnnexure(true);
    this.isView = false;
    this.isAnnexure = false;
    this.isupdateAnnexure = false;
    this.show_only_annexure = true;
  }
  
  view(index) {
    this.selectedResult = this.filteredMaterials[index];
    if (this.selectedResult['familyList'].length > 0)
      this.family_members = this.selectedResult['familyList'];
   if (this.selectedResult['qualiList'].length > 0)
      this.academics = JSON.parse(this.selectedResult['qualiList']);
  if (this.selectedResult['employeement_list'].length > 0)
      this.employeement = JSON.parse(this.selectedResult['employeement_list']);
    if (this.selectedResult['salary_list'].length > 0)
      this.amtList = JSON.parse(this.selectedResult['salary_list']);
   if (this.selectedResult['qualiList'].length > 0)
      this.qualiList = JSON.parse(this.selectedResult['qualiList']);
   if (this.selectedResult['Languages'].length > 0)
      this.languages = JSON.parse(this.selectedResult['Languages']);
    if (this.selectedResult['document_list1'].length > 0)
      this.documents = this.selectedResult['document_list1'];

    this.isView = true;
    this.getSalaryAnnexure(true);
    this.show_only_annexure = true;
  }

  
  edit(index) {
   // this.router.navigate(['/ho-hr/employees/edit/' + this.employees[index].emp_id]);
   this.router.navigate(
    ['//ho-hr/employees/edit/'],
    { queryParams: { emp_id:  this.filteredMaterials[index].emp_id } }
  );
    this.isedit = true;
  }
  inactive(index){
    this.selectedResult = this.filteredMaterials[index];
    // if (!data.valid) {
    //   alertify.error('All fields are required');
    //   return;
    // }
    this.service.post('hr/employee.php?type=inactive_emp&id=' + this.selectedResult['id'], JSON.stringify(this.selectedResult)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Updated Successfully');
        this.isPassword = false;
        this.emp_id = '';
        this.getEmployees();
      } else {
        alertify.success('Employee Deleted Successfully');
      }
    });
  }

  del(emp_id) {
    this.emp_id = emp_id;
    this.isPassword = true;
  }

  verify(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service.post('hr/employee.php?type=delEmployee&emp_code=' + this.emp_id, JSON.stringify(data.value)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Record Updated Successfully');
        this.isPassword = false;
        this.emp_id = '';
        this.getEmployees();
      } else {
        alertify.error('Invalid Password');
      }
    });
  }
  downloadEmpForm() {
    this.service.open('hr/employee.php?type=downloadEmployeeform&id=' + this.selectedResult['emp_id']);
  }
  downloadSalaryAnnexure() {
    this.service.open('hr/employee.php?type=download_salary_annexure&emp_code=' + this.selectedResult['emp_id']+'&salary='+this.take_home_salary);
  }
  download() {
    this.service.open('hr/employee.php?type=downloadEmployeeList&department_name=' + this.department + '&designation=' + this.designation + '&status=' + this.status);
  }

  AllRecord() {
    this.service.get('hr/employee.php?type=getAllEmployeesList').subscribe((response: any) => {
      this.employees = response;
    });
    this.department = '';
    this.designation = '';
    this.status = '';
  }
  getSalaryAnnexure(only_annexure) {
    this.earnings_list = [];
    this.detuctions_list = [];
    this.ctc_list = [];
    this.service.get('hr/employee.php?type=getEmpSalaryAnnexure&id=' + this.selectedResult['annexure_id']).subscribe((response: any) => {
      this.salary_annexure_response = response;
      this.earnings_list = this.salary_annexure_response[0]['earnings'];
      this.detuctions_list = this.salary_annexure_response[0]['deductions'];
      this.ctc_list = this.salary_annexure_response[0]['ctc'];
      this.total_earn_annum_amt = +this.salary_annexure_response[0]['total_earnings'] * 12;
      this.total_earn_month_amt = this.salary_annexure_response[0]['total_earnings'];
      this.total_ctc_ded_month_amt = this.salary_annexure_response[0]['ctc_deductions'];
      this.total_ded_annum_amt = +this.salary_annexure_response[0]['total_deductions'];
      this.take_home_salary = this.salary_annexure_response[0]['take_home_salary'];
      this.total_ctc = this.salary_annexure_response[0]['total_ctc'];
      this.ctc_annual = this.salary_annexure_response[0]['ctc_annual'];
      this.show_only_annexure = only_annexure;

    });
  }

  /*Salary Annexure Functionality*/

  addAnnexure(i) {
    this.earnings_list=[];
    this.gross_salary=0;
    this.ctc_annual=0;
    const index = this.filteredMaterials.findIndex((employee, idx) => idx === i);
    if (index !== -1) {
        this.selectedResult = this.filteredMaterials[index];
        // Do something with this.selectedResult
    } else {
        console.error('Index not found');
    }
    // this.selectedResult = this.employees[i];
    this.emp_name = this.selectedResult['firstname'] + ' ' + this.selectedResult['middlename'] + ' ' + this.selectedResult['lastname'] + '(' + this.selectedResult['emp_id'] + ')';
    this.isAnnexure = true;
    this.isView = false;
  }
  last_per_annum_ctc:number;
  rise;
  new_per_annum_ctc;
  new_per_month_ctc;
  addIncrementAnnexure(i) {
    const index = this.filteredMaterials.findIndex((employee, idx) => idx === i);
    if (index !== -1) {
        this.selectedResult = this.filteredMaterials[index];
    } else {
        console.error('Index not found');
    }
    this.emp_name = this.selectedResult['firstname'] + ' ' + this.selectedResult['middlename'] + ' ' + this.selectedResult['lastname'] + '(' + this.selectedResult['emp_id'] + ')';
    this.rise = this.selectedResult['rise'];
    this.isupdateAnnexure = true;
    this.last_per_annum_ctc=this.selectedResult['ctc_annual']/12;
    let a = ((Number(this.selectedResult['ctc_annual'])* Number(this.rise))/100).toFixed(2);
    let b = (Number(this.selectedResult['ctc_annual']) + Number(a)).toFixed(2)
    let c = (Number(b)/12).toFixed(2);
     this.ctc_annual=b;
     this.gross_salary=Number(c);
    this.isView = false;
    console.log(this.rise);
    console.log(this.ctc_annual);
    console.log(this.gross_salary);




  }
  getSalaryHeads() {
    this.service.get('hr/salaryhead.php?type=getSalaryHead&emp_type=' + this.emp_type).subscribe((response: any) => {
      this.salary_response = response;
      this.calculateSalary();

    });
  }
  deduct=0;
  calculateSalary33(){
    let a =  (this.ctc_annual/100)*10;
    let b = this.ctc_annual
    let c= b/12;
    console.log('a='+a)
    console.log('b='+b)
    console.log('c='+c)
   
    
    this.gross_salary=c;
  }
  earnings_list1=[];
  deduct_binus=0;
  bvb=0;
  sumperbonus=0;
  total_Bonus=0;
  calculateSalary() {
    this.sumperbonus=0;
    // if (this.emp_type == undefined || this.emp_type == '') {
    //   alertify.error('Please select Emp Type to proceed');
    //   return;
    // }
  
    // let a =  (this.ctc_annual/110)*10;/
    let b = this.ctc_annual;
     let c= Number(b/12).toFixed(4);
    // console.log('a='+a)
    // console.log('b='+b)
    // console.log('c='+c)
    // this.deduct=a
     this.gross_salary=Number(c);
 
    this.total_earn_annum_amt = 0;
    this.total_earn_month_amt = 0;
    this.total_ded_month_amt = 0;
    this.total_ded_annum_amt = 0;
    this.total_ctc_ded_annum_amt = 0;
    this.total_ctc_ded_month_amt = 0;
    this.calculateble_gross = this.gross_salary;
    this.fixed_earnings_total = 0;
    this.take_home_salary = 0;
    this.total_ctc = 0;
    this.special_allowance = 0;
   
   
 
  
 
   
    // this.calcBonus();
  }
 
  saveSalaryAnnexure(Form) {
    const temp = Form.value;
    if (this.earnings_list == undefined || this.earnings_list.length == 0) {
      alertify.error('Please enter gross salary to prepare Salary Annexure');
      return;
    }
    temp['Offer_letter'] ='With Annexure';
    temp['type'] ='Generate_annexure';
    temp['annual_bonus'] =this.total_Bonus;

    temp['earnings'] = this.earnings_list;
    temp['deductions'] = this.detuctions_list;
    temp['ctc'] = this.ctc_list;
    temp['type'] = 'Employee';
    temp['total_earnings'] = this.total_earn_month_amt;
    temp['total_deductions'] = this.total_ctc_ded_month_amt;
    temp['ctc_deductions'] = this.total_ded_month_amt;
    temp['total_ctc'] = this.gross_salary;
    temp['ctc_annual'] = this.ctc_annual;
    temp['take_home_salary'] = this.gross_salary;
    
    temp['emp_id'] =  this.selectedResult['emp_id'];
    console.log(JSON.stringify(temp));
    

    this.service.post('hr/candidate.php?type=generateOffterLetter', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isView = false;
        this.isAnnexure = false;
        alertify.success('Record Generated Successfully !');
        this.emp_type='';
        this.getEmployees();
        this.getSalaryTypes();
      } else {
        alertify.error('Something went to wrong. Try Again!...');
      }
    });
  }
  applicable_date;
  saveincreaseSalaryAnnexure(increamentForm) {
    const temp = increamentForm.value;
    if (this.earnings_list == undefined || this.earnings_list.length == 0) {
      alertify.error('Please enter gross salary to prepare Salary Annexure');
      return;
    }
    temp['Offer_letter'] ='With Annexure';
    temp['type'] ='Generate_annexure';
    temp['earnings'] = this.earnings_list;
    temp['deductions'] = this.detuctions_list;
    temp['ctc'] = this.ctc_list;
    temp['type'] = 'Employee';
    temp['total_earnings'] = this.total_earn_month_amt;
    temp['total_deductions'] = this.total_ctc_ded_month_amt;
    temp['ctc_deductions'] = this.total_ded_month_amt;
    temp['total_ctc'] = this.gross_salary;
    temp['ctc_annual'] = this.ctc_annual;
    temp['take_home_salary'] = Number(this.total_earn_month_amt) - Number(this.total_ded_month_amt) - Number(this.deduct_binus);
    
    temp['emp_id'] =  this.selectedResult['emp_id'];
    // temp['earnings'] = this.earnings_list;
    // temp['deductions'] = this.detuctions_list;
    // temp['ctc'] = this.ctc_list;
    // temp['type'] = 'Employee';
    // temp['total_earnings'] = this.total_earn_month_amt;
    // temp['total_deductions'] = this.total_ctc_ded_month_amt;
    // temp['ctc_deductions'] = this.total_ded_month_amt;
    // temp['total_ctc'] = this.gross_salary;
    // temp['ctc_annual'] = this.ctc_annual;
    // temp['applicable_date'] = this.applicable_date;
    // temp['take_home_salary'] = Number(this.total_earn_month_amt) - Number(this.total_ded_month_amt);
    // temp['emp_id'] = this.selectedResult['emp_id'];
    console.log(JSON.stringify(temp));
    

    this.service.post('hr/candidate.php?type=generateincrementOffterLetter', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isView = false;
        this.isAnnexure = false;
        alertify.success('Record Generated Successfully !');
        increamentForm.resetForm();
        this.router.navigate(['/ho-hr/employees/increament']);
        this.getEmployees();
      } else {
        alertify.error('Something went to wrong. Try Again!...');
      }
    });
  }
  viewChallan(url) {
    // url = this.service.url + 'upload/challan/' + url;
    // window.open(url, '_blank');
    // window.open(this.service.url+ this.selectedReport['challan_file']);
    //  window.open(this.service.url+'upload/employee' + this.selectedResult['photo']);
    url = this.service.url + '../../upload/employee/' + url;
    window.open(url, '_blank');
  }
  viewResume(url) {
    // url = this.service.url + 'upload/challan/' + url;
    // window.open(url, '_blank');
    // window.open(this.service.url+ this.selectedReport['challan_file']);
    //  window.open(this.service.url+'upload/employee' + this.selectedResult['photo']);
    url = this.service.url + '../../upload/employee/' + url;
    window.open(url, '_blank');
  }

  exportToExcel(): void {
    const fileName = 'employee_data.xlsx';
    const sheetName = 'Employee Data';
  
    // Create a new workbook
    const workbook: XLSX.WorkBook = XLSX.utils.book_new();
  
    // Extract data from employees array
    const data: any[] = this.filteredMaterials.map(employee => [
      employee.emp_id,
      employee.employee_type,
      employee.take_home_salary,
      `${employee.firstname} ${employee.middlename} ${employee.lastname}`,
      employee.department,
      employee.joining_status === 'confirmd' ? 'CONFIRMED' : employee.joining_status,
      employee.designation,
      employee.branch,
      employee.status.toUpperCase()
    ]);
  
    // Add the data to a new worksheet
    const worksheet: XLSX.WorkSheet = XLSX.utils.aoa_to_sheet([
      ['Emp Id', 'Sal. Grade', 'Monthly CTC', 'Name', 'Department', 'Joining Status', 'Designation', 'Branch', 'Status'],
      ...data
    ]);
  
    // Append the worksheet to the workbook
    XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
  
    // Save the workbook to a file
    XLSX.writeFile(workbook, fileName);
  }

  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.employees; // If search query is empty or whitespace, return all materials
    }
    
    const query = this.searchQuery.toLowerCase().trim(); // Convert search query to lowercase and trim whitespace
  
    return this.employees.filter(material => {
      // Check if any field of the material contains the search query
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          // Convert the value to a Date object if it's not already
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          // Check if the date value is valid and includes the search query
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          // Convert field value to lowercase and check if it includes the search query
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }
}
