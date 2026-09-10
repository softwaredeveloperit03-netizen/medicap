import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QamanagerdelayapprovalComponent } from './qamanagerdelayapproval.component';

describe('QamanagerdelayapprovalComponent', () => {
  let component: QamanagerdelayapprovalComponent;
  let fixture: ComponentFixture<QamanagerdelayapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QamanagerdelayapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(QamanagerdelayapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
