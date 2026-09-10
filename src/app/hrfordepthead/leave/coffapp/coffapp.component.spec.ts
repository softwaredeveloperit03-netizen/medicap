import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoffappComponent } from './coffapp.component';

describe('CoffappComponent', () => {
  let component: CoffappComponent;
  let fixture: ComponentFixture<CoffappComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CoffappComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CoffappComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
