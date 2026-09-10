import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ScrapListComponent } from './scrap-list.component';

describe('ScrapListComponent', () => {
  let component: ScrapListComponent;
  let fixture: ComponentFixture<ScrapListComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ScrapListComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ScrapListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
