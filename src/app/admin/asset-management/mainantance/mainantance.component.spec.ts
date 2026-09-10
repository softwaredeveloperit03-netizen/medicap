import { ComponentFixture, TestBed } from '@angular/core/testing';

import { MainantanceComponent } from './mainantance.component';

describe('MainantanceComponent', () => {
  let component: MainantanceComponent;
  let fixture: ComponentFixture<MainantanceComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ MainantanceComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(MainantanceComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
