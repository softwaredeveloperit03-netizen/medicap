import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-offerletter',
  templateUrl: './offerletter.component.html',
  styleUrls: ['./offerletter.component.css']
})
export class OfferletterComponent implements OnInit {
  salary_response = [];
  offerletter = [];
  candidates = [];
  salary_heads;
  earnings_list = [];
  detuctions_list = [];
  ctc_list = [];
  isEdit = false;
  total_earnings = 0;
  total_deductions = 0;
  total_ctc = 0;
  selectedResult = [];
  total_earn_month_amt = 0;
  total_earn_annum_amt = 0;
  total_ded_month_amt = 0;
  total_ded_annum_amt = 0;
  departments;
  isNew = false;
  designations;
  qualifications;
  isMetro = 'Non Metro';
  isPF = 'Yes';
  isESIC = 'No';
  salary_types;
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
  total_ctc_ded_month_amt = 0;
  total_ctc_ded_annum_amt = 0;
  /* isNew = false;
 
  isMetro = 'Non Metro';
  isPF = 'Yes';
  isESIC = 'No';

  gross = 0;
  deduction = 0;
  contribution = 0;
  inhand = 0;
  ctc = 0;

  basic = 0;
  HRA = 0;
  Conveyance = 0;
  medical = 0;
  specialAllowance = 0;
  educationalAllowance = 0;
  equipments;
  PF = 0;
  ESIC = 0;
  c_ESIC = 0;
  bonus = 0;
  p_tax = 200;
 special_allowance
  other = 0;
 */
  take_home_salary = 0;
  contribution_annual = 0;
  ctc_annual = 0;
  special_allowance = 0
  constructor(private service: DataAccessService,private router: Router) { }

  ngOnInit() {
    this.getgenerated();
    this.getpendingcandidate();
    this.getDepartments();
    this.getDesignations();
    this.getSalaryTypes();
  }
  //----------------------For Pagination---------------------------------//

  currentPage: number = 1;
  pageSize: number = 10; // Default page size

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * 10 ;
  }
  
  onPageChange(page: number) {
    this.currentPage = page;
    console.log(this.currentPage);
  }
  
  onPageSizeChange(event: any) {
    this.pageSize = parseInt(event.target.value, 10); // Parse the selected value to an integer
  }
  viewf(){
    //this.isView=false;
    //  this.getLogs();
    this.currentPage=1;
    this.pageSize =10;
    
  }
  // ---------------------------------------------------------------------//
  norecord = false;
  getgenerated() {
    this.service.get('hr/candidate.php?type=generatedoffer').subscribe((response: any) => {
      this.offerletter = response;
      this.filterItem();
    });
  }
  getSalaryTypes() {
    this.service.get('hr/salaryhead.php?type=getSalaryTypes').subscribe((response: any) => {
      this.salary_types = response;
    });
  }
  openNew() {
    this.isNew = true;
    this.getpendingcandidate();
  }
  getDepartments() {
    this.service.get('common.php?type=getNonTechnicalDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getDesignations() {
    this.service.get('hr/employee.php?type=getDesignations').subscribe(response => {
      this.designations = response;
    });
  }
  getpendingcandidate() {
    this.service.get('hr/candidate.php?type=pendingoffer').subscribe((response: any) => {
      this.candidates = response;
    });
  }
  
  getSalaryHeads() {
    this.service.get('hr/salaryhead.php?type=getSalaryHead&emp_type=' + this.emp_type).subscribe((response: any) => {
      this.salary_response = response;
      this.calculateSalary();

    });
  }
  calculateSalary() {
    if (this.emp_type == undefined || this.emp_type == '') {
      alertify.error('Please select Emp Type to proceed');
      return;
    }
    if (Number(this.gross_salary) == 0) {
      return;
    }
    this.salary_heads = [];
    this.earnings_list = [];
    this.detuctions_list = [];
    this.ctc_list = [];
    var earnings_list = this.salary_response['Earnings'];
    var detuctions_list = this.salary_response['Deductions'];
    var ctc_list = this.salary_response['CTC'];
    if (earnings_list == undefined || earnings_list.length == 0) {
      alertify.error('Salary Heads not configured for  ' + this.emp_type);
      return;
    }
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
    // for (let i = 0; i < earnings_list.length; i++) {
    //   if (earnings_list[i]['calc_type'] != 'Percentage') {
    //     this.fixed_earnings_total = this.fixed_earnings_total + Number(earnings_list[i]['amount']);
    //     this.calculateble_gross = (this.gross_salary - this.fixed_earnings_total)
    //   }
    // }
    // if (this.calculateble_gross == 0) {
    //   this.calculateble_gross = this.gross_salary;
    // }

    let basic_salary = 0;
    for (let i = 0; i < earnings_list.length; i++) {
      if (earnings_list[i]['calc_type'] == 'Percentage') {
        var amount = parseInt(((this.calculateble_gross * Number(earnings_list[i]['percent'])) / 100).toFixed(2));
        if (earnings_list[i]['type_flag'] == 'BASIC') {
          basic_salary = amount;
        } else if (earnings_list[i]['type_flag'] == 'HRA') {
          amount = parseInt(((basic_salary * Number(earnings_list[i]['percent'])) / 100).toFixed(2));
        }
      } else {
        var amount = parseInt(Number(earnings_list[i]['amount']).toFixed(2));
      }

      if (earnings_list[i]['type_flag'] == 'ESIC') {
        if (Number(this.calculateble_gross) <= 21000) {
          var obj = { "Group": "Earnings", "calc_type": earnings_list[i]['calc_type'], "value": earnings_list[i]['calc_type'] == 'Percent' ? earnings_list[i]['percent'] : earnings_list[i]['amount'], "salary_head": earnings_list[i]['salary_head'], "Amount": amount, "Annum": (amount * 12) }
          this.earnings_list.push(obj);
        } else {
          obj = { "Group": "Earnings", "calc_type": earnings_list[i]['calc_type'], "value": earnings_list[i]['calc_type'] == 'Percent' ? earnings_list[i]['percent'] : earnings_list[i]['amount'], "salary_head": 'Others', "Amount": amount, "Annum": (amount * 12) }
          this.earnings_list.push(obj);
        }

      }
      else {
        var obj = { "Group": "Earnings", "calc_type": earnings_list[i]['calc_type'], "value": earnings_list[i]['calc_type'] == 'Percent' ? earnings_list[i]['percent'] : earnings_list[i]['amount'], "salary_head": earnings_list[i]['salary_head'], "Amount": amount, "Annum": (amount * 12) }
        if (amount > 0) {
          this.earnings_list.push(obj);
        }
      }
      this.total_earn_month_amt = Number(this.total_earn_month_amt) + Number(amount);
      this.total_earn_annum_amt = Number(this.total_earn_annum_amt) + (Number(amount) * 12);
      this.total_earnings = this.total_earn_month_amt;
    }

    //Special Allowance
    obj = { "Group": "Earnings", "calc_type": 'Auto', "value": null, "salary_head": 'Special Allowance', "Amount": 0, "Annum": 0 }
    this.earnings_list.push(obj);



    for (let i = 0; i < detuctions_list.length; i++) {
      let amount = 0;
      if (detuctions_list[i]['type_flag'] == 'ESIC' && (Number(this.calculateble_gross) > 21000)) {
        continue;
      }
      if (detuctions_list[i]['calc_type'] == 'Percentage') {
        amount = parseInt(((basic_salary * Number(detuctions_list[i]['percent'])) / 100).toFixed(2));
      } else {
        amount = parseInt(Number(detuctions_list[i]['amount']).toFixed(2));
      }

      this.total_ded_month_amt = Number(this.total_ded_month_amt) + Number(amount);
      this.total_ded_annum_amt = Number(this.total_ded_annum_amt) + (Number(amount) * 12);
      var obj = { "Group": "Deductions", "calc_type": detuctions_list[i]['calc_type'], "value": detuctions_list[i]['calc_type'] == 'Percentage' ? detuctions_list[i]['percent'] : detuctions_list[i]['amount'], "salary_head": detuctions_list[i]['salary_head'], "Amount": amount, "Annum": (amount * 12) }
      this.detuctions_list.push(obj);
      this.total_deductions = this.total_ded_month_amt;

    }

    for (let i = 0; i < ctc_list.length; i++) {
      let amount = 0;
      if (ctc_list[i]['type_flag'] == 'ESIC' && (Number(this.calculateble_gross) > 21000)) {
        continue;
      }
      //var amount = parseInt(Number(ctc_list[i]['amount']).toFixed(2));
      if (ctc_list[i]['calc_type'] == 'Percentage') {
        amount = parseInt(((basic_salary * Number(ctc_list[i]['percent'])) / 100).toFixed(2));
      } else {
        amount = parseInt(Number(ctc_list[i]['amount']).toFixed(2));
      }
      this.total_ctc_ded_month_amt = Number(this.total_ctc_ded_month_amt) + Number(amount);
      this.total_ctc_ded_annum_amt = Number(this.total_ctc_ded_annum_amt) + (Number(amount) * 12);
      var obj = { "Group": "CTC Calculations", "calc_type": ctc_list[i]['calc_type'], "value": ctc_list[i]['calc_type'] == 'Percentage' ? ctc_list[i]['percent'] : ctc_list[i]['amount'], "salary_head": ctc_list[i]['salary_head'], "Amount": amount, "Annum": (amount * 12) }
      this.ctc_list.push(obj);
      this.total_ctc = Number(this.total_ctc) + Number(ctc_list[i]['amount']);
    }
    //this.take_home_salary = (Number(this.total_earnings) - (Number(this.total_deductions)));
    this.total_ctc = (Number(this.total_earnings) + (Number(this.total_ctc_ded_month_amt)));
    this.ctc_annual = this.total_ctc * 12;
    this.special_allowance = (Number(this.gross_salary) - Number(this.total_ctc));
    // if (Number(this.special_allowance) > 0) {
    for (let i = 0; i < this.earnings_list.length; i++) {
      if (this.earnings_list[i]['salary_head'] == 'Special Allowance') {
        this.earnings_list[i]['Amount'] = this.special_allowance;
        this.earnings_list[i]['Annum'] = Number(this.special_allowance) * 12;
        this.total_earn_month_amt = (Number(this.total_earn_month_amt) + Number(this.special_allowance));
        this.total_earn_annum_amt = (Number(this.total_earn_month_amt) * 12);
        this.total_ctc = (Number(this.total_earn_month_amt) + (Number(this.total_ctc_ded_month_amt)));
        this.ctc_annual = this.total_ctc * 12;
      }
    }
    // } 
    // this.take_home_salary = ((Number(this.total_earnings)+ Number(this.total_ctc)) - (Number(this.total_deductions)));
  }
  setGrossSalary(){

  }
  saveSalaryAnnexure(Form) {
    const temp = Form.value;
    if (this.earnings_list == undefined || this.earnings_list.length == 0) {
      alertify.error('Please enter gross salary to prepare Salary Annexure');
      return;
    }
    temp['earnings'] = this.earnings_list;
    temp['deductions'] = this.detuctions_list;
    temp['ctc'] = this.ctc_list;
    temp['type'] = 'Employee';
    temp['total_earnings'] = this.total_earn_month_amt;
    temp['total_deductions'] = this.total_ctc_ded_month_amt;
    temp['ctc_deductions'] = this.total_ded_month_amt;
    temp['total_ctc'] = this.gross_salary;
    temp['ctc_annual'] = this.ctc_annual;
    temp['take_home_salary'] = Number(this.total_earn_month_amt) - Number(this.total_ded_month_amt);
    console.log(JSON.stringify(temp));

    this.service.post('hr/candidate.php?type=generateOffterLetter', JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        this.isNew = false;
        alert('Record Generated Successfully !');
        this.router.navigate(['/hr/recruitment/offerletter']);
        
      }
    });


  }

  print(): void {
    let printContents, popupWin;
    printContents = document.getElementById('print-section').innerHTML;
    popupWin = window.open('', '_blank', 'top=0,left=0,height=100%,width=auto');
    popupWin.document.open();
    popupWin.document.write(`
      <html>
        <head>
          <style>
          //........Customized style.......
          </style>
        </head>
     <body onload="window.print();window.close()">${printContents}</body>
      </html>`
    );
    popupWin.document.close();
  }
  /* 
    downloadPdf(id) {
      window.open(this.service.url+'pdf/pdfhrDepartment.php?type=downloadappointment&id='+id+'&token=' + localStorage.getItem('token'));
    } */

  downloadPdf(id) {
    this.service.open('reports/hr.php?type=generate_offer_letter&id=' + id);   
   // window.open(this.service.url + 'reports/hr.php?type=generate_offer_letter&id=' + id + '&token=' + localStorage.getItem('token'));
  }

  filterItem() {
    this.item = [];
    for (let i = 0; i < this.offerletter.length; i++) {
      let material = this.offerletter[i];
      if (material['candidate_name'].toUpperCase().includes(this.candidate_name.toUpperCase())) {
        this.item[this.item.length] = material;
      }
    }
  }
  download() {
    this.service.open('hr/candidate.php?type=downloadOfferLetter');
  }
  saveEmployeeType(data){
     if (!data.valid) {
      alertify.error('Please Enter required Field');
      return;
    }
    let temp= data.value;
    this.service.post('hr/salaryhead.php?type=save_salary_type', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('Record Inserted Successfully');
        data.resetForm();
        this.isEdit = false;
        this.getSalaryTypes()
      } else {
        alertify.error(response['status']);
      }
    });
  }
  /* 
    emailOffer(id) {
      this.service.get('pdf/pdfhrDepartment.php?type=emailappointment&id='+id).subscribe(response => {
        if (response['status'] == "success") {
          alert('Email send successfully');
        } else {
          alert('An error occured');
        }
      });
    } */


    editType(val){
      if(val=='ADD NEW'){
        this.isEdit = true;
      }else{
        this.getSalaryHeads();
      }
    }
  emailOffer(id) {
    this.service.get('pdf/pdfhrDepartment.php?type=emailofferletter&id=' + id).subscribe(response => {
      if (response['status'] == "success") {
        alertify.success('Email send successfully');
      } else {
        alertify.errror('An error occured');
      }
    });
  }

  update(status,id){
    let temp={};
    temp['status']=status;
    temp['id']=id;
    this.service.post('hr/candidate.php?type=update_offer_status', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] === 'success') {
        alertify.success('Record Inserted Successfully');
      
        
        this.getpendingcandidate()
      } else {
        alertify.error(response['status']);
      }
    });
  }
 
}
