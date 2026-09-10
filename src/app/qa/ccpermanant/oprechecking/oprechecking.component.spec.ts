import { ComponentFixture, TestBed } from '@angular/core/testing';

import { OprecheckingComponent } from './oprechecking.component';

describe('OprecheckingComponent', () => {
  let component: OprecheckingComponent;
  let fixture: ComponentFixture<OprecheckingComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ OprecheckingComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(OprecheckingComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
