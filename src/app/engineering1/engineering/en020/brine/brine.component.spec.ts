import { ComponentFixture, TestBed } from '@angular/core/testing';

import { BrineComponent } from './brine.component';

describe('BrineComponent', () => {
  let component: BrineComponent;
  let fixture: ComponentFixture<BrineComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ BrineComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(BrineComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
