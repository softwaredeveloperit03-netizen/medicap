import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DeptreviewComponent } from './deptreview.component';

describe('DeptreviewComponent', () => {
  let component: DeptreviewComponent;
  let fixture: ComponentFixture<DeptreviewComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DeptreviewComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(DeptreviewComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
