 import { Component, OnInit, SecurityContext } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DomSanitizer,SafeHtml } from '@angular/platform-browser';
declare let alertify;
@Component({
  selector: 'app-sopchecking',
  templateUrl: './sopchecking.component.html',
  styleUrls: ['./sopchecking.component.css']
})
export class SopcheckingComponent implements OnInit {

  init = ({
    height: 300,
    menubar: true,
    content_style: 'body { font-size: 12pt; font-family: Times; }',
    plugins: [
      'advlist autolink lists link image charmap print preview anchor',
      'searchreplace visualblocks code fullscreen',
      'insertdatetime media table paste code help wordcount'
    ],
    toolbar:
      'formatselect | bold italic backcolor | \
      alignleft aligncenter alignright alignjustify | \
      bullist numlist outdent indent | removeformat | help'
  });
  
  api = 'lh5ymzb4rorw2zhscucerx1013ntad53j7jjnoiokc0pjg8v';
  
  isView = false;
  results;

  departments;
  selectedResult = [];
  remark = '';

  constructor(private service: DataAccessService, private sanitizer: DomSanitizer) { }

  ngOnInit(): void {
    this.getDepartments();
    this.getpendinginitiation();
    this.get_format();
  }
  formats;
  get_format() {
    this.service.get('sops1.php?type=get_format').subscribe(response => {
      this.formats = response;
    });
  }

  getDepartments() {
    this.service.get('sops.php?type=getDepartments').subscribe(response => {
      this.departments = response;
    });
  }

  getpendinginitiation() {
    this.service.get('sops.php?type=getpendinginitiation').subscribe(response => {
      this.results = response;
      this.sanitizedEditorData = this.sanitizer.bypassSecurityTrustHtml(this.results[0]['purpose']); // Sanitize the data

    });
  }
  view_format:any;
  selectedformats=[];
  purposeArray=[];
  scopeArray=[];
  roleArray=[];
  definitionArray=[];
  referenceArray=[];
  processArray=[];
  proceduresArray=[];
  abbreviationsArray=[];
  trainingArray=[];
  distributionArray=[];
  attachmentsArray=[];
  revisionArray=[];
  purposetitle = 'Purpose';
  scopetitle = 'Scope';
  RolesRestitle = 'Role and Responsibility';
  definitiontitle = 'Definition';
  externaltitle = 'External References And Associated Documents';
  Processtitle = 'Process Overview';
  Proceduretitle = 'Procedure';
  Abbreviationtitle = 'Abbreviation';
  Trainingtitle = 'Training Requirement';
  Distributiontitle = 'Distribution';
  Attachmentstitle = 'Attachments';
  Revisiontitle = 'Revision History';

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
    this.selectedformats=this.formats[0]
    this.purposeArray = JSON.parse(this.selectedformats['purpose']);
    this.scopeArray = JSON.parse(this.selectedformats['scope']);
    this.roleArray = JSON.parse(this.selectedformats['role']);
    this.definitionArray = JSON.parse(this.selectedformats['definition']);
    this.referenceArray = JSON.parse(this.selectedformats['reference']);
    this.processArray = JSON.parse(this.selectedformats['process']);
    this.proceduresArray = JSON.parse(this.selectedformats['procedures']);
    this.abbreviationsArray = JSON.parse(this.selectedformats['abbreviation']);
    this.trainingArray = JSON.parse(this.selectedformats['training']);
    this.distributionArray = JSON.parse(this.selectedformats['distribution']);
    this.attachmentsArray = JSON.parse(this.selectedformats['attachments']);
    this.revisionArray = JSON.parse(this.selectedformats['revision']);

    this.purposetitle = this.purposeArray[0]?.['purposetitle'];
    this.scopetitle = this.scopeArray[0]?.['scopetitle'];
    this.RolesRestitle = this.roleArray[0]?.['RolesRestitle'];
    this.definitiontitle = this.definitionArray[0]?.['definitiontitle'];
    this.externaltitle = this.referenceArray[0]?.['externaltitle'];
    this.Processtitle = this.processArray[0]?.['Processtitle'];
    this.Proceduretitle = this.proceduresArray[0]?.['Proceduretitle'];
    this.Abbreviationtitle = this.abbreviationsArray[0]?.['Abbreviationtitle'];
    this.Trainingtitle = this.trainingArray[0]?.['Trainingtitle'];
    this.Distributiontitle = this.distributionArray[0]?.['Distributiontitle'];
    this.Attachmentstitle = this.attachmentsArray[0]?.['Attachmentstitle'];
    this.Revisiontitle = this.revisionArray[0]?.['Revisiontitle'];
    this.formatHTMLContent();
    

  }

  update(status) {
    this.service.get('sops.php?type=checkinitiation&status=' + status + '&id=' + this.selectedResult['id'] + '&remark=' + this.remark + '&ctrl_no=' + this.selectedResult['ctrl_no']).subscribe(response => {
      if (response['status'] === 'success') {
        this.isView = false;
        this.remark = '';
        this.getpendinginitiation();
        alertify.success(this.service.t('common.updatedSuccess'));
      } else {
        alertify.error(this.service.t('common.errorOccurred'));
      }
    });
  }


  // //////////////////////////////////////////////////////////////////////
  // //////////////////////////////////////////////////////////////////////
  // //////////////////////////////////////////////////////////////////////
  // //////////////////////////////////////////////////////////////////////
  // //////////////////////////////////////////////////////////////////////
  // //////////////////////////////////////////////////////////////////////
  sanitizedEditorData: any;

  sanitizeHTML(html: string): string  {
    return this.sanitizer.sanitize(SecurityContext.HTML, html) || '';
  }
  formattedPurpose: string = '';
  formattedContent: SafeHtml = '';
  formatHTMLContent() { 
    if (this.selectedResult && this.selectedResult['purpose']) {
      const parser = new DOMParser();
      const doc = parser.parseFromString(this.selectedResult['purpose'], 'text/html');
      let formattedHtml = '';

      const hasLists = doc.body.querySelector('ol') || doc.body.querySelector('ul');

      if (hasLists) {
        // Format content with lists
        const formatNode = (node: Element, numbering: string) => {
          if (node.tagName.toLowerCase() === 'li') {
            formattedHtml += numbering + ' ' + (node.textContent || '') + '<br>';
          }
          for (let i = 0; i < node.children.length; i++) {
            const childNode = node.children[i];
            if (childNode.tagName.toLowerCase() === 'ol' || childNode.tagName.toLowerCase() === 'ul') {
              const listItems = childNode.querySelectorAll('li');
              for (let j = 0; j < listItems.length; j++) {
                const listItem = listItems[j];
                const newNumbering = numbering + '.' + (j + 1);
                formatNode(listItem, newNumbering);
              }
            }
          }
        };
        formatNode(doc.body, '1');
      } else {
        // No lists found, use the content as-is
        formattedHtml = this.selectedResult['purpose'];
      }

      // Sanitize and bind the HTML content
      this.formattedContent = this.sanitizeHTML(formattedHtml);
      console.log('formattedContent:', this.formattedContent);
    }
  }
  // ///////////////////////////////
  formattedContentscope: SafeHtml = '';
  formatHTMLContentscope() { 
    if (this.selectedResult && this.selectedResult['scope']) {
      const parser = new DOMParser();
      const doc = parser.parseFromString(this.selectedResult['scope'], 'text/html');
      let formattedHtml = '';

      const hasLists = doc.body.querySelector('ol') || doc.body.querySelector('ul');

      if (hasLists) {
        // Format content with lists
        const formatNode = (node: Element, numbering: string) => {
          if (node.tagName.toLowerCase() === 'li') {
            formattedHtml += numbering + ' ' + (node.textContent || '') + '<br>';
          }
          for (let i = 0; i < node.children.length; i++) {
            const childNode = node.children[i];
            if (childNode.tagName.toLowerCase() === 'ol' || childNode.tagName.toLowerCase() === 'ul') {
              const listItems = childNode.querySelectorAll('li');
              for (let j = 0; j < listItems.length; j++) {
                const listItem = listItems[j];
                const newNumbering = numbering + '.' + (j + 1);
                formatNode(listItem, newNumbering);
              }
            }
          }
        };
        formatNode(doc.body, '1');
      } else {
        // No lists found, use the content as-is
        formattedHtml = this.selectedResult['scope'];
      }

      // Sanitize and bind the HTML content
      this.formattedContentscope = this.sanitizeHTML(formattedHtml);
      console.log('formattedContentscope:', this.formattedContentscope);
    }
  }

  }

// }

