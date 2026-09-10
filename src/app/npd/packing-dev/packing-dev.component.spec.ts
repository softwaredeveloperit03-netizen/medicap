import { ComponentFixture, TestBed } from '@angular/core/testing';

import { PackingDevComponent } from './packing-dev.component';

describe('PackingDevComponent', () => {
  let component: PackingDevComponent;
  let fixture: ComponentFixture<PackingDevComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ PackingDevComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(PackingDevComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
