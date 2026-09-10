import { ComponentFixture, TestBed } from '@angular/core/testing';

import { FrompoComponent } from './frompo.component';

describe('FrompoComponent', () => {
  let component: FrompoComponent;
  let fixture: ComponentFixture<FrompoComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ FrompoComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(FrompoComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
