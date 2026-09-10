import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-list',
  templateUrl: './list.component.html',
  styleUrls: ['./list.component.css']
})
export class ListComponent implements OnInit {

  
  constructor(private service: DataAccessService, private router: Router) {}

 
  ngOnInit() {
    this.getEmployees();
    this.get_rights();
  }

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';

  rights;
 

  get_rights() {
    this.service.get('hr/employee.php?type=getrights&emp_id=' +localStorage.getItem('emp_id') +'&dep_name=' +localStorage.getItem('department')).subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
      });
  }
 
  employees;
  getEmployees() {
    new Promise((res,rej)=>{
    this.service.get('hr/employee.php?type=getEmployeesList').subscribe(response => {
      this.employees = response;
      res(response);
    });
    })
  }
 
  isView = false;
  selectedResult: any = {};
 
  view(data) {
    this.selectedResult = data || {};
    this.isView = true;
  }

  isCanadaCountry(country: string | null | undefined): boolean {
    return String(country || '').trim() === 'Canada';
  }

  isIndiaCountry(country: string | null | undefined): boolean {
    return String(country || '').trim() === 'India';
  }

  getSin(emp: any): string {
    if (!emp || !this.isCanadaCountry(emp.permanent_country)) {
      return '';
    }
    return emp.pan || '';
  }

  getPan(emp: any): string {
    if (!emp || !this.isIndiaCountry(emp.permanent_country)) {
      return '';
    }
    return emp.pan || '';
  }

  getEmergencyContact(emp: any): string {
    return emp?.branch_name || '';
  }

  getTransitNo(emp: any): string {
    const routing = String(emp?.ifsc_neft || '').trim();
    if (!routing) return '';
    const parts = routing.split('-');
    return parts[0] || routing;
  }

  getInstitutionNo(emp: any): string {
    const routing = String(emp?.ifsc_neft || '').trim();
    if (!routing) return '';
    const parts = routing.split('-');
    return parts.length > 1 ? parts.slice(1).join('-') : '';
  }

  displayValue(value: unknown): string {
    if (value == null || value === '') {
      return 'NA';
    }
    const text = String(value).trim();
    return text === '' ? 'NA' : text;
  }

  formatDate(value: unknown): string {
    if (value == null || value === '') {
      return 'NA';
    }
    const d = new Date(value as string);
    if (isNaN(d.getTime())) {
      return 'NA';
    }
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}-${month}-${year}`;
  }

  viewDoc(url) {
    url = this.service.url + '../../upload/employee/' + url;
    window.open(url, '_blank');
  }

  
  ediEMp(data) {
   this.router.navigate(['/hr/employees/edit/'],{ queryParams: { emp_id:  data['emp_id'] } });
  }


  activateInActivatedEmployee(data,status){
    let temp ={};
    this.service.post('hr/employee.php?type=activateInActivatedEmployee&status='+status+'&id=' + data['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Employee '+status+' Successfully');
        this.getEmployees();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }
 
 
  searchQuery;
  get filteredMaterials(): any[] {
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return this.employees;
    }
    
    const query = this.searchQuery.toLowerCase().trim();
  
    return this.employees.filter(material => {
      return Object.entries(material).some(([key, value]) => {
        if (key === 'entry_date') {
          const dateValue = typeof value === 'string' ? new Date(value) : value;
          return dateValue instanceof Date && dateValue.toISOString().slice(0, 10).includes(query);
        } else {
          return value && value.toString().toLowerCase().includes(query);
        }
      });
    });
  }

  exportToExcel(): void {
    let csvContent = '';

    const headers = ['Sr. No.', 'Employee ID', 'Employment Source', 'Employee Type', 'First Name', 'Middle Name',
    'Last Name', 'Contact No', 'Emergency Contact', 'Email Id', 'Department', 'Position',
    'Joining Status', 'Gender', 'Date Of Birth', 'Joining Date', 'Employee Level', 'Government Issued ID',
    'SIN/PAN', 'Nationality', 'Marital Status', 'Bank Name', 'Transit #', 'Institution #', 'Account #'];

    csvContent += headers.join(',') + '\n';

    this.filteredMaterials.forEach((item, index) => {
      const row = [
        index + 1,
        this.displayValue(item.emp_id),
        this.displayValue(item.employement_type),
        this.displayValue(item.employee_type),
        this.displayValue(item.firstname),
        this.displayValue(item.middlename),
        this.displayValue(item.lastname),
        this.displayValue(item.contact_no),
        this.displayValue(this.getEmergencyContact(item)),
        this.displayValue(item.emp_email),
        this.displayValue(item.department),
        this.displayValue(item.designation),
        this.displayValue(item.joining_status),
        this.displayValue(item.gender),
        this.formatDate(item.birthdate),
        this.formatDate(item.joining_date),
        this.displayValue(item.emp_level),
        this.displayValue(item.adhar),
        this.isCanadaCountry(item.permanent_country) ? this.displayValue(this.getSin(item)) : this.displayValue(this.getPan(item)),
        this.displayValue(item.nationality),
        this.displayValue(item.marital_status),
        this.displayValue(item.bank_name),
        this.displayValue(this.getTransitNo(item)),
        this.displayValue(this.getInstitutionNo(item)),
        this.displayValue(item.acc_no),
      ];
      csvContent += row.join(',') + '\n';
    });

    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    if (link.download !== undefined) {
      const url = URL.createObjectURL(blob);
      link.setAttribute('href', url);
      link.setAttribute('download', 'employee_list.csv');
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    }
  }

  downloadPDF(): void {
    this.service.open('hr/employee.php?type=downloadEmployeesListLog');
  }

  downloadEmployeePDF(): void {
    if (!this.selectedResult?.emp_id) {
      alertify.error('No employee selected');
      return;
    }
    this.service.open(
      'hr/employee.php?type=downloadEmployeeform&id=' + encodeURIComponent(this.selectedResult.emp_id)
    );
  }

}
