import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoffComponent } from './coff.component';

describe('CoffComponent', () => {
  let component: CoffComponent;
  let fixture: ComponentFixture<CoffComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CoffComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CoffComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
