import { ComponentFixture, TestBed } from '@angular/core/testing';

import { QaApprovalComponent } from './qa-approval.component';

describe('QaApprovalComponent', () => {
  let component: QaApprovalComponent;
  let fixture: ComponentFixture<QaApprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ QaApprovalComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(QaApprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
