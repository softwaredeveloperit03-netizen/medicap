import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ReqcheckingComponent } from './reqchecking.component';

describe('ReqcheckingComponent', () => {
  let component: ReqcheckingComponent;
  let fixture: ComponentFixture<ReqcheckingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ReqcheckingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ReqcheckingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
