import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InitialPqComponent } from './initial-pq.component';

describe('InitialPqComponent', () => {
  let component: InitialPqComponent;
  let fixture: ComponentFixture<InitialPqComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InitialPqComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InitialPqComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
