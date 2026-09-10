import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReqapprovalComponent } from './reqapproval.component';

describe('ReqapprovalComponent', () => {
  let component: ReqapprovalComponent;
  let fixture: ComponentFixture<ReqapprovalComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReqapprovalComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReqapprovalComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
