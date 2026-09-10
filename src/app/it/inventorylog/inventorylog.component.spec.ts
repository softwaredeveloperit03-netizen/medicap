import { ComponentFixture, TestBed } from '@angular/core/testing';

import { InventorylogComponent } from './inventorylog.component';

describe('InventorylogComponent', () => {
  let component: InventorylogComponent;
  let fixture: ComponentFixture<InventorylogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ InventorylogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(InventorylogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
