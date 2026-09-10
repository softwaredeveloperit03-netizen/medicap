import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PackingGrnComponent } from './packing-grn.component';

describe('PackingGrnComponent', () => {
  let component: PackingGrnComponent;
  let fixture: ComponentFixture<PackingGrnComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PackingGrnComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(PackingGrnComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
