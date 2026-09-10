import { ComponentFixture, TestBed } from '@angular/core/testing';

import { ScrapLogComponent } from './scrap-log.component';

describe('ScrapLogComponent', () => {
  let component: ScrapLogComponent;
  let fixture: ComponentFixture<ScrapLogComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ ScrapLogComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(ScrapLogComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
